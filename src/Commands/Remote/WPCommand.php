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
     * @inheritdoc
     */
    protected $unavailable_commands = [
        'db' => '',
    ];

    /**
     * Run an arbitrary WP-CLI commands on a site's environment
     *
     * @authorize
     *
     * @command remote:wp
     * @aliases wp
     *
     * @param string $site_env_id Name of the environment to run the WP-CLI command on.
     * @param array $wp_command WP-CLI command to invoke on the environment
     * @return string Output of the given WP-CLI command executed on the site environment
     *
     * @usage terminus wp <site>.<env> -- <command>
     *    Runs the WP-CLI command <command> on the <env> environment of <site>
     */
    public function wpCommand($site_env_id, array $wp_command)
    {
        $this->prepareEnvironment($site_env_id);

        return $this->executeCommand($wp_command);
    }
}
