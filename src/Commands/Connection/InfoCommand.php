<?php

namespace Pantheon\Terminus\Commands\Connection;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;

use Pantheon\Terminus\Commands\TerminusCommand;
use Terminus\Collections\Sites;
use Terminus\Models\Environment;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Connection
 */
class InfoCommand extends TerminusCommand
{
    /**
     * Retrieve connection info for a specific environment such as git, sftp, mysql, redis
     *
     * @authorized
     *
     * @command connection:info
     *
     * @field-labels
     *   sftp_username: SFTP Username
     *   sftp_host: SFTP Host
     *   sftp_password: SFTP Password
     *   sftp_url: SFTP URL
     *   sftp_command: SFTP Command
     *   git_username: Git Username
     *   git_host: Git Host
     *   git_port: Git Port
     *   git_url: Git URL
     *   git_command: Git Command
     *   mysql_host: Mysql Host
     *   mysql_username: Mysql Username
     *   mysql_password: Mysql Password
     *   mysql_port: Mysql Port
     *   mysql_database: Mysql Database
     *   mysql_url: Mysql URL
     *   mysql_command: Mysql Command
     *
     * @param string $environment Name of the environment to retrieve
     *
     * @return AssociativeList
     *
     * @usage connection:info awesome-site.dev --format=json
     *   Display connection information in json format
     * @usage connection:info awesome-site.dev --fields=mysql_command --format=string
     *   Display connection information only for the given parameter
     * @usage connection:info awesome-site.dev --fields=git_*
     *   Display all of the connection information fields related to git.
     *
     */
    public function connectionInfo($environment)
    {
        $site_env = explode('.', $environment);
        if (count($site_env) != 2) {
            throw new \Exception('The environment argument must be given as <site_name>.<environment>');
        }

        $sites = new Sites();
        $site  = $sites->get($site_env[0]);
        $env   = $site->environments->get($site_env[1]);

        return new AssociativeList($env->connectionInfo());
    }
}
