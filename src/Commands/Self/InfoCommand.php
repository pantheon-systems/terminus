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
        return new PropertyList($this->getConfig()->serialize());
    }
}
