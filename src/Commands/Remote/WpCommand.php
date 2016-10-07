<?php

namespace Pantheon\Terminus\Commands\Remote;

/**
 * Command to proxy WP-CLI commands on an Environment using SSH
 *
 * @package Pantheon\Terminus\Commands\Remote
 */
class WpCommand extends SSHBaseCommand
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
     * Run arbitrary WP-CLI commands on a site environment
     *
     * @command remote:wp
     * @aliases wp
     *
     * @authenticated
     *
     * @param string $site_env_id Name of the environment to run the WP-CLI command on.
     * @param array $wp_command WP-CLI command to invoke on the environment
     *
     * @return string Output of the given WP-CLI command executed on the site environment
     */
    public function wpCommand($site_env_id, array $wp_command)
    {
        $this->prepareEnvironment($site_env_id);

        return $this->executeCommand($wp_command);
    }
}
