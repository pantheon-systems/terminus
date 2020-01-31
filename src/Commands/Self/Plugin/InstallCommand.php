<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Consolidation\AnnotatedCommand\CommandData;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Plugins\PluginInfo;
use PHP_CodeSniffer\Tokenizers\PHP;

/**
 * Installs a Terminus plugin using Composer.
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    const ALREADY_INSTALLED_MESSAGE = '{project} is already installed.';
    const COMPOSER_INSTALL_COMMAND =
        'composer create-project --stability=%s --prefer-source --keep-vcs -n -d %s %s:~%s';
    const INVALID_PROJECT_MESSAGE = '{project} is not a valid Packagist project.';
    const USAGE_MESSAGE = 'terminus self:plugin:<install|add> <Packagist project 1> [Packagist project 2] ...';

    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @param array $projects A list of one or more plugin projects to install
     * @option string $stability Version stability such as stable, beta, alpha, etc.
     *
     * @usage <Packagist project 1> [Packagist project 2] ...
     */
    public function install(array $projects, $options = ['stability' => 'stable',])
    {
        foreach ($projects as $project_name) {
            if ($this->validateProject($project_name)) {
                $messages = $this->doInstallation($project_name, $options['stability']);
                foreach ($messages as $message) {
                    $this->log()->notice($message);
                }
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
     * @param string $stability stable, beta, alpha, etc
     * @return array $messages
     */
    private function doInstallation($project_name, $stability)
    {
        preg_match('/(\d*).\d*.\d*/', $this->getConfig()->get('version'), $version_matches);
        $terminus_major_version = $version_matches[1];
        preg_match('/.*\/(.*)/', $project_name, $plugin_name_matches);
        $install_dir = $this->getConfig()->get('plugin_dir') . DIRECTORY_SEPARATOR . $plugin_name_matches[1];

        $command = sprintf(
            self::COMPOSER_INSTALL_COMMAND,
            $stability,
            $install_dir,
            $project_name,
            $terminus_major_version
        );
        $this->log()->debug("Running: $command");
        exec($command, $messages);
        $this->log()->debug("Returned:" . PHP_EOL . implode(PHP_EOL, $messages));
        return $messages;
    }

    /**
     * @param string $project_name
     * @return bool
     */
    private function validateProject($project_name)
    {
        if (!self::isValidPackagistProject($project_name)) {
            $this->log()->error(self::INVALID_PROJECT_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        if ($this->isInstalled($project_name)) {
            $this->log()->notice(self::ALREADY_INSTALLED_MESSAGE, ['project' => $project_name,]);
            return false;
        }

        return true;
    }

    /**
     * Check whether a Packagist project is valid.
     *
     * @param string $project_name Name of plugin package to install
     * @return bool True if valid, false otherwise
     */
    private static function isValidPackagistProject($project_name)
    {
        // Search for the Packagist project.
        exec(sprintf(PluginInfo::VALIDATION_COMMAND, $project_name), $items);
        if (empty($items)) {
            return false;
        }
        $item = array_shift($items);
        return ($item === $project_name);
    }
}
