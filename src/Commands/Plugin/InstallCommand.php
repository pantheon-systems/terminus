<?php

namespace Pantheon\Terminus\Commands\Plugin;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;

/**
 * Manage Terminus plugins.
 *
 * @package Pantheon\Terminus\Commands\Plugin
 */
class InstallCommand extends PluginBaseCommand
{
    /**
     * Install one or more Terminus plugins.
     *
     * @command plugin:install
     * @aliases plugin:add
     *
     * @option array $projects A list of one or more plugin projects to install
     *
     * @usage <URL to plugin archive, Git or Packagist project 1> [URL to plugin archive, Git or Packagist project 2] ...
     */
    public function install(array $projects)
    {
        // @TODO: Add the ability to search and prompt to install new plugins.

        if (empty($projects)) {
            $message = "Usage: terminus plugin:<install|add>";
            $message .= " <URL to plugin archive, Git or Packagist project 1>";
            $message .= " [URL to plugin archive, Git or Packagist project 2] ...";
            throw new TerminusNotFoundException($message);
        }

        $plugins_dir = $this->getPluginDir();
        foreach ($projects as $project) {
            $is_url = (filter_var($project, FILTER_VALIDATE_URL) !== false);
            if (!$is_url) {
                if (!$this->commandExists('composer')) {
                    $message = "In order to install Packagist plugin projects, you need to install Composer.";
                    $this->log()->notice($message);
                } elseif (!$this->isValidPackagistProject($project)) {
                    $message = "{$project} is not a valid Packagist project.";
                    $this->log()->error($message);
                } else {
                    $path = explode('/', $project);
                    $plugin = $path[1];
                    if (is_dir($plugins_dir . $plugin)) {
                        $message = "{$plugin} is already installed.";
                        $this->log()->notice($message);
                    } else {
                        exec("composer create-project --prefer-source --keep-vcs -n -d {$plugins_dir} {$project}:~1", $messages);
                        foreach ($messages as $message) {
                            $this->log()->notice($message);
                        }
                    }
                }
            } else {
                if ($ext = pathinfo(parse_url($project, PHP_URL_PATH), PATHINFO_EXTENSION)) {
                    switch ($ext) {
                        case 'git':
                            $parts = parse_url($project);
                            $path = explode('/', $parts['path']);
                            $plugin = array_pop($path);
                            $repository = $parts['scheme'] . '://' . $parts['host'] . implode('/', $path);
                            if (!$this->isValidGitRepository($repository, $plugin)) {
                                $message = "{$project} is not a valid Git repository.";
                                $this->log()->error($message);
                            } elseif (is_dir($plugins_dir . $plugin)) {
                                $message = "{$plugin} is already installed.";
                                $this->log()->notice($message);
                            } elseif ($this->commandExists('git')) {
                                exec("git clone --branch 1.x {$project} {$plugins_dir}{$plugin}", $messages);
                                foreach ($messages as $message) {
                                    $this->log()->notice($message);
                                }
                            } else {
                                $message = "In order to clone Git repository projects, you need to install Git.";
                                $this->log()->notice($message);
                            }
                            break;

                        case 'gz':
                            if ($this->commandExists('curl') && $this->commandExists('tar')) {
                                exec("curl {$project} -L | tar -C {$plugins_dir} -xvz", $messages);
                            } else {
                                $messages[] = "In order to install archive projects, you need to install curl and tar.";
                            }
                            foreach ($messages as $message) {
                                $this->log()->notice($message);
                            }
                    }
                } else {
                    $message = "{$project} is not a valid project URL.";
                    $this->log()->error($message);
                }
            }
        }
    }
}
