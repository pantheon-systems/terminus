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
     * @inheritdoc
     */
    protected $valid_frameworks = [
        'wordpress',
        'wordpress_network',
    ];

    /**
     * Runs a WP-CLI command remotely on a site's environment.
     *
     * @authorize
     *
     * @command remote:wp
     * @aliases wp
     *
     * @param string $site_env_id Site & environment in the format `site-name.env`
     * @param array $wp_command WP-CLI command
     * @return string Command output
     *
     * @usage terminus wp <site>.<env> -- <command>
     *     Runs the Drush command <command> remotely on <site>'s <env> environment.
     */
    public function wpCommand($site_env_id, array $wp_command)
    {
        $this->prepareEnvironment($site_env_id);
        return $this->executeCommand($wp_command);
    }
}
