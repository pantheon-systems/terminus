<?php

namespace Pantheon\Terminus\Commands\Remote;

/**
 * Class WPCommand
 * A command to proxy WP-CLI commands on an environment using SSH
 * @package Pantheon\Terminus\Commands\Remote
 */
class WPCommand extends SSHBaseCommand
{
    /**
     * @inheritdoc
     */
    protected $command = 'wp';

    /**
     * Runs a WP-CLI command remotely on a site's environment.
     *
     * @authorize
     *
     * @command remote:wp
     * @aliases wp
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param array $wp_command WP-CLI command
     * @param array $options Commandline options
     * @option progress Allow progress bar to be used (tty mode only)
     * @return string Command output
     *
     * @usage <site>.<env> -- <command> Runs the WP-CLI command <command> remotely on <site>'s <env> environment.
     * @usage <site>.<env> --progress -- <command> Runs a WP-CLI command with a progress bar
     */
    public function wpCommand($site_env, array $wp_command, array $options = ['progress' => false])
    {
        $this->prepareEnvironment($site_env);
        $this->setProgressAllowed($options['progress']);
        return $this->executeCommand($wp_command);
    }
}
