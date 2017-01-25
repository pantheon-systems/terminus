<?php

namespace Pantheon\Terminus\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Self
 */
class InfoCommand extends TerminusCommand
{
    /**
     * Displays the local PHP and Terminus environment configuration.
     *
     * @command self:info
     *
     * @field-labels
     *     php_binary_path: PHP binary
     *     php_version: PHP version
     *     php_ini: php.ini used
     *     project_config_path: Terminus project config
     *     terminus_path: Terminus root dir
     *     terminus_version: Terminus version
     *     os_version: Operating system
     * @return PropertyList
     *
     * @usage Displays the local PHP and Terminus environment configuration.
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
        return new PropertyList($info);
    }
}
