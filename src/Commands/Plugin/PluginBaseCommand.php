<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

/**
 * Class PluginBaseCommand
 * Base class for Terminus commands that deal with sending Plugin commands
 * @package Pantheon\Terminus\Commands\Plugin
 */
abstract class PluginBaseCommand extends TerminusCommand
{
    /**
     * Get plugin projects.
     *
     * @param string $plugins_dir Plugins directory
     * @return array Plugin projects
     */
    protected function getPluginProjects($plugins_dir)
    {
        $projects = array();
        $finder = new Finder();
        $finder->files()->in($plugins_dir);
        foreach ($finder as $file) {
            $path = $file->getRelativePath();
            // Get the parent path only.
            if (!strpos($path, '/')) {
                // Make sure the path is unique.
                if (!in_array($path, $projects)) {
                    $projects[] = $path;
                }
            }
        }
        return $projects;
    }

    /**
     * Get the plugin directory.
     *
     * @param string $plugin Plugin name
     * @return string Plugin directory
     */
    protected function getPluginDir($plugin = '')
    {
        $slash = $this->getSlash();
        $plugins_dir = getenv('TERMINUS_PLUGINS_DIR');
        $windows = (php_uname('s') == 'Windows NT');
        if (!$plugins_dir) {
            // Determine the correct $plugins_dir based on the operating system
            $home = getenv('HOME');
            if ($windows) {
                $system = '';
                if (getenv('MSYSTEM') !== null) {
                    $system = strtoupper(substr(getenv('MSYSTEM'), 0, 4));
                }
                if ($system != 'MING') {
                    $home = getenv('HOMEPATH');
                }
                $home = str_replace('\\', $slash, $home);
                $plugins_dir = $home . $slash . 'terminus' . $slash . 'plugins' . $slash;
            } else {
                $plugins_dir = $home . '/.terminus/plugins/';
            }
        } else {
            // Make sure the proper trailing slash(es) exist
            $chars = $windows ? 2 : 1;
            if (substr("$plugins_dir", -$chars) != $slash) {
                $plugins_dir .= $slash;
            }
        }
        // Create the directory if it doesn't already exist
        if (!is_dir("$plugins_dir")) {
            mkdir("$plugins_dir", 0755, true);
        }
        return $plugins_dir . $plugin;
    }

    /**
     * Get the plugin installation method.
     *
     * @param string Plugin name
     * @return string Plugin installation method
     */
    protected function getInstallMethod($plugin)
    {
        $slash = $this->getSlash();
        $plugin_dir = $this->getPluginDir($plugin);
        $git_dir = $plugin_dir . $slash . '.git';
        if (is_dir("$git_dir") && $this->commandExists('git')) {
            return 'git';
        }
        $composer_json = $plugin_dir . $slash . 'composer.json';
        if (file_exists($composer_json)) {
            return $this->commandExists('composer') ? 'composer' : 'archive';
        }
        return 'unknown';
    }

    /**
     * Update a specific plugin.
     *
     * @param string $plugin Plugin name
     */
    protected function updatePlugin($plugin)
    {
        $plugins_dir = $this->getPluginDir();
        $plugin_dir = $plugins_dir . $plugin;
        if (!is_dir("$plugin_dir")) {
            $message = "{$plugin} is not installed.";
            throw new TerminusNotFoundException($message);
        }
        $messages = array();
        $message = "Updating {$plugin}...";
        $this->log()->notice($message);
        $method = $this->getInstallMethod($plugin);
        switch ($method) {
            case 'git':
                // Compare installed version to the latest release.
                $installed_version = $this->getInstalledVersion($plugin_dir);
                $latest_version = $this->getLatestVersion($plugin_dir);
                if ($installed_version != 'unknown' && $latest_version != 'unknown') {
                    if ($installed_version < $latest_version) {
                        exec("cd \"$plugin_dir\" && git checkout {$latest_version}", $messages);
                    } else {
                        $messages[] = "Already up-to-date.";
                    }
                } else {
                    $messages[] = "Unable to update.  Semver compliance issue with tagged release.";
                }
                break;

            case 'composer':
                // Determine the project name.
                $composer_info = $this->getComposerInfo($plugin);
                $project = $composer_info['name'];
                $packagist_url = "https://packagist.org/packages/{$project}";
                if ($this->isValidUrl($packagist_url)) {
                    // Get the Terminus major version.
                    $terminus_major_version = $this->getTerminusMajorVersion();
                    // Backup the plugin directory, just in case.
                    $datetime = date('YmdHi', time());
                    $backup_directory = "~/.terminus/backups/plugins/{$project}/{$datetime}";
                    exec("mkdir -p {$backup_directory} && tar czvf {$backup_directory}/backup.tar.gz \"{$plugin_dir}\"", $backup_messages);
                    // Create a new project via Composer.
                    $composer_command = "composer create-project --prefer-source --keep-vcs -n -d {$plugins_dir} {$project}:~{$terminus_major_version}";
                    exec("rm -rf \"{$plugin_dir}\" && {$composer_command}", $install_messages);
                    $messages = array_merge($backup_messages, $install_messages);
                    $messages[] = "Backed up the project to {$backup_directory}/backup.tar.gz.";
                } else {
                    $messages[] = "Unable to update.  {$packagist_url} is not a valid Packagist project.";
                }
                break;

            case 'archive':
            default:
                $messages[] = "Unable to update.  Plugin is not a Composer project or Git repository.";
        }
        foreach ($messages as $message) {
            $this->log()->notice($message);
        }
    }

    /**
     * Get the currently installed plugin version.
     *
     * @param string $plugin Path to plugin
     * @return string Installed plugin version
     */
    protected function getInstalledVersion($plugin)
    {
        exec("cd \"$plugin\" && git describe --tags", $tags);
        if (!empty($tags)) {
            $version = array_pop($tags);
        } else {
            exec("cd \"$plugin\" && git rev-parse --abbrev-ref HEAD", $branch);
            $version = !empty($branch) ? array_pop($branch) : 'unknown';
        }
        return $version;
    }

    /**
     * Get the latest available plugin version.
     *
     * @param string $plugin Path to plugin
     * @return string Latest plugin version
     */
    protected function getLatestVersion($plugin)
    {
        // Get the Terminus major version.
        $terminus_major_version = $this->getTerminusMajorVersion();
        exec("cd \"$plugin\" && git fetch --all && git tag -l | grep ^{$terminus_major_version} | sort -r | head -1", $tag);
        if (!empty($tag)) {
            $version = array_pop($tag);
        } else {
            exec("cd \"$plugin\" && git rev-parse --abbrev-ref HEAD", $branch);
            $version = !empty($branch) ? array_pop($branch) : 'unknown';
        }
        return $version;
    }

    /**
     * Check whether a Git repository is valid.
     *
     * @param string $repository Repository URL
     * @param string $plugin Plugin name
     * @return string Plugin title, if found, otherwise, empty string
     */
    protected function isValidGitRepository($repository, $plugin)
    {
        // Make sure the URL is valid.
        $is_url = (filter_var($repository, FILTER_VALIDATE_URL) !== false);
        if (!$is_url) {
            return '';
        }
        // Make sure a subpath exists.
        $parts = parse_url($repository);
        if (!isset($parts['path']) || ($parts['path'] == '/')) {
            return '';
        }
        // Search for a plugin title.
        $plugin_data = @file_get_contents($repository . '/' . $plugin);
        if (!empty($plugin_data)) {
            preg_match('|<title>(.*)</title>|', $plugin_data, $match);
            if (isset($match[1])) {
                $title = $match[1];
                if (stripos($title, 'terminus') !== false && stripos($title, 'plugin') !== false) {
                    return $title;
                }
                return '';
            }
            return '';
        }
        return '';
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project Packagist project name
     * @return bool True if valid, false otherwise
     */
    protected function isValidPackagistProject($project)
    {
        $valid = false;
        // Search for the Packagist project.
        exec("composer search -N -t terminus-plugin {$project}", $items);
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($item == $project) {
                    $valid = true;
                    break;
                }
            }
        }
        return $valid;
    }

    /**
     * Check whether a URL is valid.
     *
     * @param string $url The URL to check
     * @return bool True if the URL returns a 200 status, false otherwise
     */
    protected function isValidUrl($url = '')
    {
        // @TODO: This could be a generic utility function used by other commands.

        if (!$url) {
            return false;
        }
        $headers = @get_headers($url);
        if (!isset($headers[0])) {
            return false;
        }
        return (strpos($headers[0], '200') !== false);
    }

    /**
     * Get the plugin Composer information.
     *
     * @param string $plugin Plugin name
     * @return array of Composer information
     */
    protected function getComposerInfo($plugin)
    {
        // @TODO: This could be a generic utility function used by other commands.

        $slash = $this->getSlash();
        $plugin_dir = $this->getPluginDir($plugin);
        $composer_json = $plugin_dir . $slash . 'composer.json';
        if (file_exists($composer_json)) {
            $composer_data = @file_get_contents($composer_json);
            return (array)json_decode($composer_data);
        }
        return array();
    }

    /**
     * Platform independent check whether a command exists.
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    protected function commandExists($command)
    {
        // @TODO: This could be a generic utility function used by other commands.

        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Get platform independent directory separator.
     *
     * @return string Directory separator
     */
    protected function getSlash()
    {
        // @TODO: This could be a generic utility function used by other commands.

        $windows = (php_uname('s') == 'Windows NT');
        if ($windows) {
            $slash = '\\\\';
        } else {
            $slash = '/';
        }
        return $slash;
    }

    /**
     * Get the Terminus major version.
     *
     * @return integer Terminus major version
     */
    protected function getTerminusMajorVersion()
    {
        // @TODO: This could be a generic utility function used by other commands.

        $terminus_version = $this->getConfig()->get('version');
        $version_parts = explode('.', $terminus_version);
        return $version_parts[0];
    }
}
