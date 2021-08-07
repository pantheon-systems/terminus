<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;

/**
 * Installs a Terminus plugin using Composer.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    const ALREADY_INSTALLED_MESSAGE = '{project} is already installed.';
    const INSTALL_COMMAND =
        'composer require -d {dir} {project} --prefer-source';
    const INVALID_PROJECT_MESSAGE = '{project} is not a valid Packagist project.';
    const USAGE_MESSAGE = 'terminus self:plugin:<install|add> <Packagist project 1> [Packagist project 2] ...';
    const DEPENDENCIES_REQUIRE_COMMAND = 'composer require -d {dir} {packages}';
    const COMPOSER_ADD_REPOSITORY = 'composer config -d {dir} repositories.{repo_name} path {path}';

    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @param array $projects A list of one or more plugin projects to install. Projects may include version constraints.
     *
     * @usage <project 1> [project 2] ...
     */
    public function install(array $projects)
    {
        foreach ($projects as $project_name) {
            if ($this->validateProject($project_name)) {
                $results = $this->doInstallation($project_name);
                // TODO Improve messaging
                $this->log()->notice($results['output']);
            }
        }
    }

    /**
     * Check for minimum plugin command requirements.
     * @hook validate self:plugin:install
     * @param CommandData $commandData
     */
    public function validate(CommandData $commandData)
    {
        $this->checkRequirements();

        if (empty($commandData->input()->getArgument('projects'))) {
            throw new TerminusNotFoundException(self::USAGE_MESSAGE);
        }
    }

    /**
     * @param string $project_name Name of project to be installed
     * @return array Results from the install command
     */
    private function doInstallation($project_name)
    {
        $plugin_name = PluginInfo::getPluginNameFromProjectName($project_name);
        $config = $this->getConfig();
        $plugins_dir = $config->get('plugins_dir');
        $dependencies_dir = $config->get('dependencies_dir');
        $this->ensureComposerJsonExists($plugins_dir, 'pantheon-systems/terminus-plugins');
        // @todo Kevin: When to initialize and copy the resolved stuff? What if I git pull/composer install on terminus folder?
        $this->ensureComposerJsonExists($dependencies_dir, 'pantheon-systems/terminus-dependencies');
        $this->updateTerminusDependencies($dependencies_dir, $plugins_dir);

        $command = str_replace(
            ['{dir}', '{project}',],
            [$plugins_dir, $project_name,],
            self::INSTALL_COMMAND
        );
        $results = $this->runCommand($command);
        $this->log()->notice('Installed {project_name}.', compact('project_name'));

        // @todo Kevin: should I return the output of this?
        $this->addPackageToTerminusDependencies($dependencies_dir, $plugins_dir, $project_name);

        return $results;
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
        // @todo Kevin what if error?
        if ($results['exit_code'] === 0) {
            $command = str_replace(
                ['{dir}', '{packages}',],
                [$dependencies_dir, $package . ':*',],
                self::DEPENDENCIES_REQUIRE_COMMAND
            );
            // @todo Kevin capture the exit code?
            $this->runCommand($command);
        }
    }

    /**
     * Require terminus resolved packages into terminus-dependencies folder.
     */
    protected function updateTerminusDependencies($dependencies_dir, $plugins_dir) {
        // @todo Kevin Move to parent and invoke from other commands.
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
            // @todo Kevin what if error?
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
        }
    }

    /**
     * @param string $path Path where composer.json file should exist.
     * @param string $package_name Package name to create if composer.json doesn't exist.
     */
    private function ensureComposerJsonExists($path, $package_name)
    {
        $this->ensureDirectoryExists($path);
        if (!$this->getLocalMachine()->getFileSystem()->exists($path . '/composer.json')) {
            $this->runCommand("composer --working-dir=${path} init --name=${package_name} -n");
            $this->runCommand("composer --working-dir=${path} config minimum-stability dev");
        }
    }

    /**
     * @param string $path
     * @param int $permissions
     */
    private function ensureDirectoryExists($path, $permissions = 0755)
    {
        $this->getLocalMachine()->getFileSystem()->mkdir($path, $permissions);
    }

    /**
     * @param string $project_name
     * @return bool
     */
    private function validateProject($project_name)
    {
        if (!PluginInfo::checkWhetherPackagistProject($project_name, $this->getLocalMachine())) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        if ($this->isInstalled($project_name)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        return true;
    }
}
