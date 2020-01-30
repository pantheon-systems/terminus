<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginDiscovery;

/**
 * Class PluginBaseCommand
 * Base class for Terminus commands that deal with sending Plugin commands
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
abstract class PluginBaseCommand extends TerminusCommand
{
    const COMPOSER_METHOD = 'composer';
    const GET_BRANCH_INSTALLED_VERSION_COMMAND = '[ -d %s ] && cd %s && git rev-parse --abbrev-ref HEAD';
    const GET_NONSTABLE_LATEST_VERSION_COMMAND =
        '[ -d %s ] && cd %s && git tag -l --sort=version:refname | grep %s | sort -r | xargs';
    const GET_STABLE_LATEST_VERSION_COMMAND =
        '[ -d %s ] && cd %s && git fetch --all && git tag -l --sort=version:refname | grep ^[v%s] | sort -r | head -1';
    const GET_TAGS_INSTALLED_VERSION_COMMAND = '[ -d %s ] && cd %s && git describe --tags';
    const GIT_METHOD = 'git';
    const INSTALL_COMPOSER_MESSAGE =
        'Please install Composer to enable plugin management. See https://getcomposer.org/download/.';
    const INSTALL_GIT_MESSAGE = 'Please install Git to enable plugin management.';
    const PROJECT_NOT_FOUND_MESSAGE = 'No project or plugin named {project} found.';
    const UNKNOWN_METHOD = 'unknown';
    const UNKNOWN_VERSION = 'unknown';
    const VALIDATION_COMMAND = 'composer search -N -t terminus-plugin %s';

    private $projects = [];

    /**
     * Check for minimum plugin command requirements.
     * @throws TerminusNotFoundException
     */
    protected function checkRequirements()
    {
        if (!$this->commandExists('composer')) {
            throw new TerminusNotFoundException(self::INSTALL_COMPOSER_MESSAGE);
        }
        if (!$this->commandExists('git')) {
            throw new TerminusNotFoundException(self::INSTALL_GIT_MESSAGE);
        }
    }

    /**
     * Platform independent check whether a command exists.
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    protected function commandExists($command)
    {
        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Get the currently installed plugin version.
     *
     * @param string $plugin Path to plugin
     * @return string Installed plugin version
     */
    protected function getInstalledVersion($plugin)
    {
        exec(
            sprintf(self::GET_TAGS_INSTALLED_VERSION_COMMAND, $plugin, $plugin),
            $tags
        );
        if (!empty($tags)) {
            $version = array_pop($tags);
            return $version;
        }

        exec(
            sprintf(self::GET_BRANCH_INSTALLED_VERSION_COMMAND, $plugin, $plugin),
            $branch
        );
        if (!empty($branch)) {
            $version = array_pop($branch);
            return $version;
        }

        return self::UNKNOWN_VERSION;
    }

    /**
     * Get the plugin installation method.
     *
     * @param string $plugin_dir The directory the plugin is installed to
     * @return string Plugin installation method
     */
    protected static function getInstallMethod($plugin_dir)
    {
        $git_dir = $plugin_dir . DIRECTORY_SEPARATOR . '.git';
        if (is_dir("$git_dir")) {
            return self::GIT_METHOD;
        }
        $composer_json = $plugin_dir . DIRECTORY_SEPARATOR . 'composer.json';
        if (file_exists($composer_json)) {
            return self::COMPOSER_METHOD;
        }
        return self::UNKNOWN_METHOD;
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
        exec(
            sprintf(
                self::GET_STABLE_LATEST_VERSION_COMMAND,
                $plugin,
                $plugin,
                $terminus_major_version
            ),
            $tag
        );
        if (!empty($tag)) {
            $version = array_pop($tag);
            // Check for non-stable semantic version (ie. -beta1 or -rc2).
            preg_match('/(v*.*)\-(.*)/', $version, $matches);
            if (!empty($matches[1])) {
                exec(
                    sprintf(
                        self::GET_NONSTABLE_LATEST_VERSION_COMMAND,
                        $plugin,
                        $plugin,
                        $terminus_major_version
                    ),
                    $releases
                );
                $stable_release = $matches[1];
                if (!empty($releases)) {
                    foreach ($releases as $release) {
                        // Update to stable release, if available.
                        if ($release === $stable_release) {
                            $version = $release;
                            break;
                        }
                    }
                }
            }
        } else {
            // Get the latest version from HEAD.
            exec(
                sprintf(self::GET_BRANCH_INSTALLED_VERSION_COMMAND, $plugin, $plugin),
                $branch
            );
            if (!empty($branch)) {
                $version = array_pop($branch);
                return $version;
            }
            return self::UNKNOWN_VERSION;
        }
        return $version;
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
        if (!is_dir($plugins_dir)) {
            mkdir($plugins_dir, 0755, true);
        }
        return $plugins_dir . DIRECTORY_SEPARATOR . $plugin;
    }

    /**
     * Get data on a specific installed plugin.
     *
     * @param string $project Name of a project or plugin
     * @return array Plugin projects
     * @throws TerminusNotFoundException
     */
    protected function getPluginProject($project)
    {
        $matches = array_filter(
            $this->getPluginProjects(),
            function($plugin_data) use ($project) {
                return in_array($project, [$plugin_data['name'], $plugin_data['project'],]);
            }
        );
        if (empty($matches)) {
            throw new TerminusNotFoundException(self::PROJECT_NOT_FOUND_MESSAGE, compact('project'));
        }
        return array_shift($matches);
    }

    /**
     * Get plugin projects.
     *
     * @return array Plugin projects
     */
    protected function getPluginProjects()
    {
        if (empty($this->projects)) {
            $this->projects = array_map(
                function ($plugin_info) {
                    $data = $plugin_info->getInfo();
                    $data['project'] = $data['name'];
                    list($data['creator'], $data['name']) = explode('/', $data['project']);
                    $data['location'] = $this->getPluginDir($data['project']);
                    $data['version'] = $this->getInstalledVersion($data['location']);
                    $data['latest_version'] = $this->getLatestVersion($data['location']);
                    $data['compatible_versions'] = $plugin_info->getCompatibleTerminusVersion();
                    $data['method'] = self::getInstallMethod($data['location']);
                    $data['packagist_url'] = 'https://packagist.org/packages/' . $data['project'];
                    return $data;
                },
                $this->getContainer()->get(PluginDiscovery::class)->discover()
            );
        }
        return $this->projects;
    }

    /**
     * Get the Terminus major version.
     *
     * @return integer Terminus major version
     */
    protected function getTerminusMajorVersion()
    {
        preg_match('/(\d*).\d*.\d*/', $this->getConfig()->get('version'), $matches);
        return $matches[1];
    }

    /**
     * Detects whether a project/plugin is installed.
     * @param string $project
     * @return bool
     */
    protected function isInstalled($project)
    {
        try {
            $this->getPluginProject($project);
        } catch (TerminusNotFoundException $e) {
            return false;
        }
        return true;
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
        exec(sprintf(self::VALIDATION_COMMAND, $project), $items);
        if (empty($items)) {
            return false;
        }
        $item = array_shift($items);
        return ($item === $project);
    }
}
