<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Finder\Finder;

/**
 * Class PluginBaseCommand
 * Base class for Terminus commands that deal with sending Plugin commands
 * @package Pantheon\Terminus\Commands\Self\Plugin
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
        $projects = [];
        $finder = new Finder();
        $finder->files()->in($plugins_dir);
        foreach ($finder as $file) {
            $path = $file->getRelativePath();
            // Get the parent path only.
            if (!strpos($path, DIRECTORY_SEPARATOR)) {
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
        $plugins_dir = $this->getConfig()->get('plugins_dir');
        // Create the directory if it doesn't already exist.
        if (!is_dir("$plugins_dir")) {
            mkdir("$plugins_dir", 0755, true);
        }
        return $plugins_dir . DIRECTORY_SEPARATOR . $plugin;
    }

    /**
     * Get the plugin installation method.
     *
     * @param string Plugin name
     * @return string Plugin installation method
     */
    protected function getInstallMethod($plugin)
    {
        $plugin_dir = $this->getPluginDir($plugin);
        $git_dir = $plugin_dir . DIRECTORY_SEPARATOR . '.git';
        if (is_dir("$git_dir")) {
            return 'git';
        }
        $composer_json = $plugin_dir . DIRECTORY_SEPARATOR . 'composer.json';
        return file_exists($composer_json) ? 'composer' : 'unknown';
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
        $cmds = "cd \"$plugin\""
            . " && git fetch --all"
            . " && git tag -l --sort=version:refname"
            . " | grep ^[v$terminus_major_version] | sort -r | head -1";
        exec($cmds, $tag);
        if (!empty($tag)) {
            $version = array_pop($tag);
            // Check for non-stable semantic version (ie. -beta1 or -rc2).
            preg_match('/(v*.*)\-(.*)/', $version, $matches);
            if (!empty($matches[1])) {
                $nonstable_cmds = "cd \"$plugin\" && git tag -l --sort=version:refname"
                    . " | grep ^[v$terminus_major_version] | sort -r | xargs";
                $stable_release = $matches[1];
                exec($nonstable_cmds, $releases);
                if (!empty($releases)) {
                    foreach ($releases as $release) {
                        // Update to stable release, if available.
                        if ($release == $stable_release) {
                            $version = $release;
                            break;
                        }
                    }
                }
            }
        } else {
            // Get the latest version from HEAD.
            exec("cd \"$plugin\" && git rev-parse --abbrev-ref HEAD", $branch);
            $version = !empty($branch) ? array_pop($branch) : 'unknown';
        }
        return $version;
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project Packagist project name
     * @return bool True if valid, false otherwise
     */
    protected function isValidPackagistProject($project)
    {
        // Search for the Packagist project.
        exec("composer search -N -t terminus-plugin {$project}", $items);
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($item == $project) {
                    return true;
                }
            }
        }
        return false;
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

        $plugin_dir = $this->getPluginDir($plugin);
        $composer_json = $plugin_dir . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($composer_json)) {
            $composer_data = @file_get_contents($composer_json);
            return (array)json_decode($composer_data);
        }
        return [];
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

    /**
     * Check for minimum plugin command requirements.
     */
    protected function checkRequirements()
    {
        if (!$this->commandExists('composer')) {
            $message = 'Please install composer to enable plugin management.  See https://getcomposer.org/download/.';
            throw new TerminusNotFoundException($message);
        }
        if (!$this->commandExists('git')) {
            $message = 'Please install git to enable plugin management.';
            throw new TerminusNotFoundException($message);
        }
    }
}
