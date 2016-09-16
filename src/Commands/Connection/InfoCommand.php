<?php

namespace Pantheon\Terminus\Commands\Connection;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

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
     * @param string $environment Name of the environment to retrieve
     * @param string $filter Parameter filter (optional)
     * @param array $options [fields=<env,param,value>] [format=<table|csv|yaml|json>]
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   env: Environment
     *   param: Parameter
     *   value: Connection Info
     *
     * @example connection:info awesome-site.dev git_command --format=json
     *   Display connection information only for the given parameter
     *
     */
    public function connectionInfo(
        $environment,
        $filter = null,
        $options = ['format' => 'table', 'fields' => 'param,value']
    ) {
        $connection_info = [[]];

        $site_env = explode('.', $environment);
        if (count($site_env) != 2) {
            $this->log()
                ->error('The environment argument must be given as <site_name>.<environment>');

            return new RowsOfFields($connection_info);
        }

        $sites = new Sites();
        $site  = $sites->get($site_env[0]);
        $env   = $site->environments->get($site_env[1]);

        $connection_info = $this->environmentParams($env, $filter);
        return new RowsOfFields($connection_info);
    }


    /**
     * Retrieve Environment#connectionInfo() in a structure suitable for formatting
     *   Ex: ['env' => 'live', 'param' => 'mysql_host', 'value' => 'onebox']
     *
     * @param Environment $environment A Terminus\Models\Environment to interrogate
     * @param string $filter An optional parameter name to filter results
     *
     * @return array of connection info parameters
     */
    protected function environmentParams($environment, $filter = null)
    {
        $params = [];
        foreach ($environment->connectionInfo() as $param => $value) {
            if (is_null($filter) or $param == $filter) {
                $params[] = array(
                    'env'   => "{$environment->site->get('name')}.{$environment->id}",
                    'param' => $param,
                    'value' => $value,
                );
            }
        }

        return $params;
    }
}
