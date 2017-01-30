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
     * Runs a Drush command remotely on a site's environment.
     *
     * @authorize
     *
     * @command remote:drush
     * @aliases drush
     *
     * @param string $site_env_id Site & environment in the format `site-name.env`
     * @param array $drush_command Drush command
     * @return string Command output
     *
     * @usage <site>.<env> -- <command> Runs the Drush command <command> remotely on <site>'s <env> environment.
     */
    public function drushCommand($site_env_id, array $drush_command)
    {
        $this->prepareEnvironment($site_env_id);
        return $this->executeCommand($drush_command);
    }
}
