<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class PluginBaseCommand
 * Base class for Terminus commands that deal with sending Plugin commands
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
abstract class PluginBaseCommand extends TerminusCommand
{
    // Messages
    const INSTALL_COMPOSER_MESSAGE =
        'Please install Composer to enable plugin management. See https://getcomposer.org/download/.';
    const INSTALL_GIT_MESSAGE = 'Please install Git to enable plugin management.';
    const PROJECT_NOT_FOUND_MESSAGE = 'No project or plugin named {project} found.';
    const DEPENDENCIES_REQUIRE_COMMAND = 'composer require -d {dir} {packages}';
    const COMPOSER_ADD_REPOSITORY = 'composer config -d {dir} repositories.{repo_name} path {path}';
    const BACKUP_COMMAND =
        "mkdir -p {backup_dir} && tar czvf {backup_dir}"
        . DIRECTORY_SEPARATOR . "backup.tar.gz \"{dir}\"";

    /**
     * @var array|null
     */
    private $projects = null;

    /**
     * @return LocalMachineHelper
     */
    protected function getLocalMachine()
    {
        return $this->getContainer()->get(LocalMachineHelper::class);
    }

    /**
     * Check for minimum plugin command requirements.
     * @throws TerminusNotFoundException
     */
    protected function checkRequirements()
    {
        if (!self::commandExists('composer')) {
            throw new TerminusNotFoundException(self::INSTALL_COMPOSER_MESSAGE);
        }
        if (!self::commandExists('git')) {
            throw new TerminusNotFoundException(self::INSTALL_GIT_MESSAGE);
        }
    }

    /**
     * Get data on a specific installed plugin.
     *
     * @param string $project Name of a project or plugin
     * @return array Plugin projects
     * @throws TerminusNotFoundException
     */
    protected function getPlugin($project)
    {
        $matches = array_filter(
            $this->getPluginProjects(),
            function($plugin) use ($project) {
                return in_array($project, [$plugin->getName(), $plugin->getPluginName(),]);
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
            $this->projects = $this->getContainer()->get(PluginDiscovery::class)->discover();
        }
        return $this->projects;
    }

    /**
     * Detects whether a project/plugin is installed.
     * @param string $project
     * @return bool
     */
    protected function isInstalled($project)
    {
        try {
            $this->getPlugin($project);
        } catch (TerminusNotFoundException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param string $command
     * @return array
     */
    protected function runCommand($command)
    {
        $this->log()->debug('Running {command}...', compact('command'));
        $results = $this->getLocalMachine()->exec($command);
        $this->log()->debug("Returned:\n{output}", $results);
        return $results;
    }

    /**
     * Platform independent check whether a command exists.
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    private static function commandExists($command)
    {
        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Returns terminus major version.
     *
     * @return int
     */
    protected function getTerminusMajorVersion() {
        $config = $this->getContainer()->get('config');
        return substr($config->get('version'), 0, 1);
    }

    /**
     * Returns terminus plugin directory.
     *
     * @return int
     */
    protected function getPluginsDir() {
        $config = $this->getContainer()->get('config');
        return $config->get('plugins_dir');
    }

    /**
     * Get packages string from composer.lock file contents.
     */
    protected function getPackagesWithVersionString($composer_lock_contents) {
        $packages = [];
        foreach ($composer_lock_contents['packages'] as $package) {
            $packages[] = $package['name'] . ':' . $package['version'];
        }
        return implode(' ', $packages);
    }

    /**
     * Get packages string from composer.json file contents.
     */
    protected function getRequiredPackages($composer_json_contents) {
        $packages = [];
        foreach ($composer_json_contents['require'] as $package_name => $version) {
            $packages[] = $package_name;
        }
        return $packages;
    }

    /**
     * Add plugin package to terminus dependencies.
     */
    protected function addPackageToTerminusDependencies($dependencies_dir, $plugins_dir, $package) {
        $repo_path = $plugins_dir . '/vendor/' . $package;
        $command = str_replace(
            ['{dir}', '{repo_name}', '{path}',],
            [$dependencies_dir, basename($repo_path), $repo_path,],
            self::COMPOSER_ADD_REPOSITORY
        );
        $results = $this->runCommand($command);
        if ($results['exit_code'] === 0) {
            $command = str_replace(
                ['{dir}', '{packages}',],
                [$dependencies_dir, $package . ':*',],
                self::DEPENDENCIES_REQUIRE_COMMAND
            );
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error requiring dependencies in terminus-dependencies.',
                    []
                );
            }
        }
        else {
            throw new TerminusException(
                'Error adding composer repository in terminus-dependencies.',
                []
            );
        }
    }

    /**
     * Require terminus resolved packages into terminus-dependencies folder.
     */
    protected function updateTerminusDependencies($dependencies_dir, $plugins_dir) {
        if (file_exists($this->getConfig()->get('root') . '/composer.lock')) {
            $terminus_composer_lock = json_decode(
                file_get_contents($this->getConfig()->get('root') . '/composer.lock'),
                true,
                10
            );
            $packages = $this->getPackagesWithVersionString($terminus_composer_lock);
            $command = str_replace(
                ['{dir}', '{packages}',],
                [$dependencies_dir, $packages,],
                self::DEPENDENCIES_REQUIRE_COMMAND
            );
            $results = $this->runCommand($command);
            if ($results['exit_code'] === 0) {
                $plugins_composer_json = json_decode(
                    file_get_contents($plugins_dir . '/composer.json'),
                    true,
                    5
                );
                $packages = $this->getRequiredPackages($plugins_composer_json);
                foreach ($packages as $package) {
                    $this->addPackageToTerminusDependencies($dependencies_dir, $plugins_dir, $package);
                }
            }
            else {
                throw new TerminusException(
                    'Error requiring dependencies in terminus-dependencies.',
                    []
                );
            }
        }
    }

    /**
     * Backup given dir.
     */
    protected function backupDir($dir, $backup_type = 'plugins') {
        $datetime = date('YmdHi', time());
        $backup_directory = str_replace(
            '/',
            DIRECTORY_SEPARATOR,
            "$plugins_dir/../backup/$backup_type/$datetime"
        );
        $command = str_replace(
            ['{backup_dir}', '{dir}',],
            [$backup_directory, $dir,],
            self::BACKUP_COMMAND
        );
        $result = $this->runCommand($command);
        if ($results['exit_code'] !== 0) {
            // Throw exception.
            throw new TerminusException(
                'Error backing up {backup_type} directory: {dir}',
                ['backup_type' => $backup_type, 'dir' => $site_name,],
                1
            );
        }
        return $backup_directory . '/backup.tar.gz';
    }

}
