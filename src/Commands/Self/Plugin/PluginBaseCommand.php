<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Plugins\PluginDiscovery;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Plugins\PluginInfo;
use Composer\Semver\Semver;
use Symfony\Component\Filesystem\Exception\IOException;

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
    const OUTDATED_COMPOSER_MESSAGE =
        'Please update Composer to enable plugin management. Run composer self-update.';
    const INSTALL_GIT_MESSAGE = 'Please install Git to enable plugin management.';
    const PROJECT_NOT_FOUND_MESSAGE = 'No project or plugin named {project} found.';
    const DEPENDENCIES_REQUIRE_COMMAND = 'composer require -d {dir} {packages}';
    const COMPOSER_ADD_REPOSITORY =
        "composer config -d {dir} repositories.{repo_name} '{\"type\": \"path\","
        . "\"url\": \"{path}\", \"options\": {\"symlink\": true}}'";
    const COMPOSER_GET_REPOSITORIES = 'composer config -d {dir} repositories';
    const BACKUP_COMMAND =
        "mkdir -p {backup_dir} && tar czvf {backup_dir}"
        . DIRECTORY_SEPARATOR . "backup.tar.gz \"{dir}\"";
    const COMPOSER_REMOVE_REPOSITORY = 'composer config -d {dir} --unset repositories.{repo_name}';
    const DEPENDENCIES_UPDATE_COMMAND = 'composer update -d {dir} {packages} --with-dependencies';
    const INSTALL_COMMAND = 'composer require -d {dir} {project} --no-update';
    const COMPOSER_VERSION_COMMAND = 'composer --version';

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
        } else {
            // Validate composer version >= 2.1.0.
            $result = $this->runCommand(self::COMPOSER_VERSION_COMMAND);
            if ($result['exit_code'] === 0) {
                $output = $result['output'];
                if (preg_match('/version\s(\d+\.\d+\.\d+)\s.+/', $output, $matches)) {
                    if (isset($matches[1])) {
                        $version = $matches[1];
                        if (!Semver::satisfies($version, '>=2.1.0')) {
                            throw new TerminusNotFoundException(self::OUTDATED_COMPOSER_MESSAGE);
                        }
                    }
                }
            }
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
            function ($plugin) use ($project) {
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
    protected function runCommand(string $command)
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
        $windows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Get packages string from composer.lock file contents.
     */
    protected function getPackagesWithVersionString($composer_lock_contents)
    {
        $packages = [];
        foreach ($composer_lock_contents['packages'] as $package) {
            $packages[] = $package['name'] . ':' . $package['version'];
        }
        return implode(' ', $packages);
    }

    /**
     * Run composer update in the given folder.
     *
     * @return array Array returned by runCommand.
     */
    protected function runComposerUpdate($folder, $packages = '')
    {
        $command = str_replace(
            ['{packages}'],
            [$packages],
            self::DEPENDENCIES_UPDATE_COMMAND
        );
        $command = self::populateComposerWorkingDir($command, $folder);
        return $this->runCommand($command);
    }

    /**
     * Require terminus resolved packages into terminus-dependencies folder.
     *
     * @return array
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function updateTerminusDependencies($source_plugins_dir = '', $source_dependencies_dir = '')
    {
        $base_dir = $this->createTempDir();
        $plugins_dir_basename = $this->getConfig()->get('plugins_dir_basename');
        $plugins_dir = $base_dir . '/' . $plugins_dir_basename;
        $dependencies_dir = $base_dir . '/terminus-dependencies';
        $fs = $this->getLocalMachine()->getFileSystem();
        if ($source_plugins_dir && is_dir($source_plugins_dir)) {
            $fs->mirror($source_plugins_dir, $plugins_dir);
        }
        if ($source_dependencies_dir && is_dir($source_dependencies_dir)) {
            $fs->mirror($source_dependencies_dir, $dependencies_dir);
        }
        $this->ensureComposerJsonExists($plugins_dir, 'pantheon-systems/terminus-plugins');
        $this->ensureComposerJsonExists($dependencies_dir, 'pantheon-systems/terminus-dependencies');
        // Get our path repositories and add the default entry
        $path_repositories = $this->getPathRepositories($plugins_dir);
        $path_repositories['pantheon-systems/terminus-plugins'] = '../' . $plugins_dir_basename;

        $terminus_composer_lock_path = $this->getConfig()->get('root') . '/composer.lock';
        if (!file_exists($terminus_composer_lock_path)) {
            throw new TerminusException('Terminus composer.lock file not found');
        }

        $terminus_composer_lock = json_decode(file_get_contents($terminus_composer_lock_path), true, 10);
        $packages = $this->getPackagesWithVersionString($terminus_composer_lock);
        // First: Require dependencies from terminus.
        $command = str_replace(
            ['{packages}'],
            [$packages],
            self::DEPENDENCIES_REQUIRE_COMMAND
        );
        $command = self::populateComposerWorkingDir($command, $dependencies_dir);
        $results = $this->runCommand($command);
        if ($results['exit_code'] !== 0) {
            throw new TerminusException(
                'Error executing command "{command}": {stderr}',
                ['command' => $command, 'stderr' => $results['stderr']]
            );
        }

        // Second: Add path repositories.
        foreach ($path_repositories as $repo_name => $path) {
            $plugins_dir_basename = $this->getConfig()->get('plugins_dir_basename');
            $command = str_replace(
                ['{repo_name}', '{path}'],
                [$repo_name, $path],
                self::COMPOSER_ADD_REPOSITORY
            );
            $command = self::populateComposerWorkingDir($command, $dependencies_dir);
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error configuring composer.json terminus-dependencies: {stderr}',
                    ['stderr' => $results['stderr']]
                );
            }
        }

        // Third: Require packages.
        $command = str_replace(
            ['{packages}'],
            ['pantheon-systems/terminus-plugins:*'],
            self::DEPENDENCIES_REQUIRE_COMMAND
        );
        $command = self::populateComposerWorkingDir($command, $dependencies_dir);
        $results = $this->runCommand($command);
        if ($results['exit_code'] === 0) {
            // Finally: Update packages.
            $results = $this->runComposerUpdate($dependencies_dir);
            if ($results['exit_code'] === 0) {
                $this->cleanupOldDependenciesFolders();
                return [
                    'plugins_dir' => $plugins_dir,
                    'dependencies_dir' => $dependencies_dir,
                ];
            }
        }

        throw new TerminusException(
            'Error updating dependencies in terminus-dependencies: {stderr}',
            ['stderr' => $results['stderr']]
        );
    }

    /**
     * Cleanup old dependencies folders.
     */
    protected function cleanupOldDependenciesFolders()
    {
        $dependencies_base_dir = $this->getConfig()->get('dependencies_base_dir');
        $current_dependencies_dir = $this->getTerminusDependenciesDir();
        $pattern_start = basename($dependencies_base_dir);
        $parent_folder = dirname($dependencies_base_dir);
        $all_folders = scandir($parent_folder);
        $fs = $this->getLocalMachine()->getFileSystem();
        foreach ($all_folders as $folder) {
            $full_path = $parent_folder . DIRECTORY_SEPARATOR . $folder;
            if (!is_dir($full_path)
                || strpos($folder, $pattern_start) !== 0
                || $full_path === $current_dependencies_dir) {
                continue;
            }
            // Delete folder if:
            // - it's a folder
            // - the folder name starts with $pattern_start
            // - it's not the current dependencies folder.
            try {
                $fs->remove($full_path);
            } catch (IOException $e) {
                $this->log()->warning(
                    'Error removing old dependencies folder: {full_path}.',
                    compact('full_path')
                );
            }
        }
    }

    /**
     * @param string $path Path where composer.json file should exist.
     * @param string $package_name Package name to create if composer.json doesn't exist.
     */
    protected function ensureComposerJsonExists($path, $package_name)
    {
        $this->ensureDirectoryExists($path);
        if (!$this->getLocalMachine()->getFileSystem()->exists($path . '/composer.json')) {
            $this->runCommand("composer --working-dir=${path} init --name=${package_name} -n");
            $this->runCommand("composer --working-dir=${path} config minimum-stability dev");
            $this->runCommand("composer --working-dir=${path} config prefer-stable true");
        }
    }

    /**
     * Return existing path repositories in given dir.
     */
    protected function getPathRepositories($plugins_dir)
    {
        $path_repositories = [];
        $command = self::populateComposerWorkingDir(self::COMPOSER_GET_REPOSITORIES, $plugins_dir);
        $results = $this->runCommand($command);
        if ($results['exit_code'] === 0) {
            $json = json_decode($results['output'], true);
            foreach ($json as $key => $repository) {
                if (isset($repository['type']) &&
                    ($repository['type'] == 'path') &&
                    isset($repository['url']) &&
                    !empty($repository['url'])
                ) {
                    $path_repositories[$key] = $repository['url'];
                }
            }
        }
        return $path_repositories;
    }

    /**
     * Create temporary dir
     */
    protected function createTempDir($prefix = 'terminus', $dir = false)
    {
        $fs = $this->getLocalMachine()->getFileSystem();
        $tempfile = $fs->tempnam($dir ? $dir : sys_get_temp_dir(), $prefix ? $prefix : '');
        if ($fs->exists($tempfile)) {
            $fs->remove($tempfile);
        }
        $fs->mkdir($tempfile, 0700);
        if (is_dir($tempfile)) {
            $this->registerCleanupFunction($tempfile);
            return $tempfile;
        }
    }

    /**
     * Register our shutdown function if it hasn't already been registered.
     */
    protected function registerCleanupFunction($path)
    {
        // Insure that $workdir will be deleted on exit.
        register_shutdown_function(function ($path) {
            $fs = $this->getLocalMachine()->getFileSystem();
            $fs->remove($path);
        }, $path);
        $registered = true;
    }

    /**
     * Replace source folder into destination.
     */
    protected function replaceFolder($source, $destination)
    {
        $fs = $this->getLocalMachine()->getFileSystem();
        if ($fs->exists($destination)) {
            $fs->remove($destination);
        }
        $fs->mirror($source, $destination);
    }

    /**
     * @param string $path
     * @param int $permissions
     */
    protected function ensureDirectoryExists($path, $permissions = 0755)
    {
        $this->getLocalMachine()->getFileSystem()->mkdir($path, $permissions);
    }

    /**
     * Gets project name from given path.
     *
     * @param string $project_or_path Project or path for the plugins.
     *
     * @return string Project name.
     */
    protected function getProjectNameFromPath(string $project_or_path)
    {
        $composerJson = $project_or_path . '/composer.json';
        $composerContents = file_get_contents($composerJson);
        // If the specified dir does not contain a terminus plugin, throw an error
        $composerData = json_decode($composerContents, true);
        if (!empty($composerData['type']) && $composerData['type'] !== 'terminus-plugin') {
            throw new TerminusException(
                'Cannot install from path {path} because the project there is not of type "terminus-plugin"',
                ['path' => $project_or_path]
            );
        }

        // If the specified dir does not have a name in the composer.json, throw an error
        if (empty($composerData['name'])) {
            throw new TerminusException(
                'Cannot install from path {path} because the project there does not have a name',
                ['path' => $project_or_path]
            );
        }

        // Finally, return the project name and let install command install it as normal.
        return $composerData['name'];
    }

    /**
     * Install given project. Optionally from path repository.
     *
     * @param string $project_name Name of project to be installed
     * @param string $instalation_path If not empty, install as a path repository
     *
     * @return array Results from the install command
     */
    protected function installProject($project_name, $instalation_path = '')
    {
        $plugin_name = PluginInfo::getPluginNameFromProjectName($project_name);
        $project_name_parts = explode(':', $project_name);
        $project_name_without_version = reset($project_name_parts);
        $config = $this->getConfig();
        $original_plugins_dir = $config->get('plugins_dir');
        $original_dependencies_dir = $this->getTerminusDependenciesDir();
        $folders = $this->updateTerminusDependencies($original_plugins_dir, $original_dependencies_dir);
        $plugins_dir = $folders['plugins_dir'];
        $dependencies_dir = $folders['dependencies_dir'];
        try {
            if (!empty($instalation_path)) {
                // Update path repository in plugins dir and dependencies dir.
                foreach ([$plugins_dir, $dependencies_dir] as $dir) {
                    $command = str_replace(
                        ['{repo_name}', '{path}'],
                        [$project_name_without_version, realpath($instalation_path)],
                        self::COMPOSER_ADD_REPOSITORY
                    );
                    $command = self::populateComposerWorkingDir($command, $dir);
                    $results = $this->runCommand($command);
                    if ($results['exit_code'] !== 0) {
                        throw new TerminusException(
                            'Error configuring path repository in {path}.',
                            ['path' => basename($dir)]
                        );
                    }
                }
            }

            $command = str_replace(
                ['{project}'],
                [$project_name],
                self::INSTALL_COMMAND
            );
            $command = self::populateComposerWorkingDir($command, $plugins_dir);
            $results = $this->runCommand($command);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException(
                    'Error requiring package in terminus-plugins: {stderr}',
                    ['stderr' => $results['stderr']]
                );
            }
            $results = $this->runComposerUpdate($dependencies_dir, $project_name_without_version);
            if ($results['exit_code'] !== 0) {
                throw new TerminusException('Error running composer update in terminus-dependencies.');
            }
            $this->replaceFolder($plugins_dir, $original_plugins_dir);
            $this->replaceFolder($dependencies_dir, $original_dependencies_dir);
            $this->log()->notice('Installed {project_name}.', compact('project_name'));
        } catch (TerminusException $e) {
            $this->log()->error($e->getMessage());
        }

        return $results;
    }

    /**
     * Returns absolute path to Terminus' Composer dependencies.
     *
     * @return string
     */
    protected function getTerminusDependenciesDir(): string
    {
        return $this->getConfig()->get('terminus_dependencies_dir');
    }

    /**
     * Replaces "{dir}" placeholder (Composer's "-d" option) in the command string with the given value.
     *
     * @param string $command
     * @param string $dir
     *
     * @return string
     */
    public static function populateComposerWorkingDir(string $command, string $dir): string
    {
        return str_replace(
            ['{dir}'],
            [$dir],
            $command
        );
    }
}
