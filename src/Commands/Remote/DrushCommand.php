<?php

namespace Pantheon\Terminus\Commands\Remote;

/**
 * Class DrushCommand
 * A command to proxy Drush commands on an environment using SSH
 * @package Pantheon\Terminus\Commands\Remote
 */
class DrushCommand extends SSHBaseCommand
{
    /**
     * @inheritdoc
     */
    protected $command = 'drush';

    /**
     * @inheritdoc
     */
    protected $valid_frameworks = [
        'drupal',
        'drupal8',
    ];

    /**
     * @inheritdoc
     */
    protected $unavailable_commands = [
        'sql-connect' => 'connection:info --field=mysql_command',
        'sql-sync'    => '',
    ];

    /**
     * Run an arbitrary Drush command on a site's environment
     *
     * @authorize
     * @site-env
     *
     * @command remote:drush
     * @aliases drush
     *
     * @param string $site_env_id Name of the environment to run the drush command on.
     * @param array $drush_command Drush command to invoke on the environment
     * @return string Output of the given drush command executed on the site environment
     *
     * @usage terminus drush <site>.<env> -- <command>
     *    Runs the Drush command <command> on the <env> environment of <site>
     */
    public function drushCommand($site_env_id, array $drush_command)
    {
        $this->prepareEnvironment($site_env_id);

        return $this->executeCommand($drush_command);
    }
}
