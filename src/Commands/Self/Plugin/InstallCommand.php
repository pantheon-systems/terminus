<?php

namespace Pantheon\Terminus\Commands\Self\Plugin;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Self\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    /**
     * Install one or more Terminus plugins.
     *
     * @command self:plugin:install
     * @aliases self:plugin:add
     *
     * @option array $projects A list of one or more plugin projects to install
     * @option string $stability Version stability such as stable, beta, alpha, etc.
     *
     * @usage <Packagist project 1> [Packagist project 2] ...
     */
    public function install(array $projects, $options = ['stability' => 'stable', ])
    {
        // Check for minimum plugin command requirements.
        $this->checkRequirements();

        if (empty($projects)) {
            $message = "Usage: terminus plugin:<install|add>";
            $message .= " <Packagist project 1> [Packagist project 2] ...";
            throw new TerminusNotFoundException($message);
        }

        $terminus_major_version = $this->getTerminusMajorVersion();
        $plugins_dir = $this->getPluginDir();
        foreach ($projects as $project) {
            if (!$this->isValidPackagistProject($project)) {
                $message = "{$project} is not a valid Packagist project.";
                $this->log()->error($message);
            } else {
                $path = explode('/', $project);
                $plugin = $path[1];
                if (is_dir($plugins_dir . $plugin)) {
                    $message = "{$plugin} is already installed.";
                    $this->log()->notice($message);
                } else {
                    exec(
                        "composer create-project --stability={$options['stability']} --prefer-source"
                        . " --keep-vcs -n -d {$plugins_dir} {$project}:~{$terminus_major_version}",
                        $messages
                    );
                    foreach ($messages as $message) {
                        $this->log()->notice($message);
                    }
                }
            }
        }
    }
}
