<?php

namespace Pantheon\Terminus\Commands\Connection;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Connection
 */
class InfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Retrieve connection info for a specific environment such as git, sftp, mysql, redis
     *
     * @authorize
     *
     * @command connection:info
     * @aliases connection
     *
     * @field-labels
     *   sftp_command: SFTP Command
     *   sftp_username: SFTP Username
     *   sftp_host: SFTP Host
     *   sftp_password: SFTP Password
     *   sftp_url: SFTP URL
     *   git_command: Git Command
     *   git_username: Git Username
     *   git_host: Git Host
     *   git_port: Git Port
     *   git_url: Git URL
     *   mysql_command: MySQL Command
     *   mysql_username: MySQL Username
     *   mysql_host: MySQL Host
     *   mysql_password: MySQL Password
     *   mysql_url: MySQL URL
     *   mysql_port: MySQL Port
     *   mysql_database: MySQL Database
     *   redis_command: Redis Command
     *   redis_port: Redis Port
     *   redis_url: Redis URL
     *   redis_password: Redis Password
     * @default-fields *_command
     * @return PropertyList
     *
     * @param string $site_env_id Name of the environment to retrieve
     *
     * @usage connection:info <site>.<env>
     *   Display connection information about the <env> environment of <site>
     * @usage connection:info <site>.<env> --fields='git_*'
     *   Display all of the connection information fields related to Git for the <env> environment of <site>
     */
    public function connectionInfo($site_env_id)
    {
        list(, $env) = $this->getSiteEnv($site_env_id);
        return new PropertyList($env->connectionInfo());
    }
}
