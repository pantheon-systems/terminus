<?php

namespace Pantheon\Terminus\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\AssociativeList;
use Pantheon\Terminus\Commands\TerminusCommand;

class InfoCommand extends TerminusCommand
{
    /**
     * Print various data about the CLI environment.
     *
     * @command self:info
     *
     * @field-labels
     *   php_binary_path: PHP binary
     *   php_version: PHP version
     *   php_ini: php.ini used
     *   project_config_path: Terminus project config
     *   terminus_path: Terminus root dir
     *   terminus_version: Terminus version
     *   os_version: Operating system
     *
     * @usage terminus self:info
     * @usage terminus site:info --field=<field>
     * Responds with the single field of terminus information
     * @usage terminus site:info --field=terminus_version --format=json
     * Outputs the Terminus version in JSON format
     *
     * @return AssociativeList
     */
    public function info()
    {
        $config = $this->getConfig();
        $info = [
            'php_binary_path'     => $config->get('php'),
            'php_version'         => $config->get('php_version'),
            'php_ini'             => $config->get('php_ini'),
            'project_config_path' => $config->get('config_dir'),
            'terminus_path'       => $config->get('root'),
            'terminus_version'    => $config->get('version'),
            'os_version'          => $config->get('os_version'),
        ];
        return new AssociativeList($info);
    }
}
