<?php

namespace Pantheon\Terminus\Commands\Self;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Robo\Contract\ConfigAwareInterface;

class ConfigDumpCommand extends TerminusCommand
{
    /**
     * Display the current Terminus configuration information
     *
     * @command self:config:dump
     *
     * @return RowsOfFields
     *
     * @usage terminus self:config-dump
     *    Show the current configuration values
     */
    public function dumpConfig()
    {
        $out = [];
        $config = $this->getConfig();
        foreach ($config->keys() as $key) {
            $out[] = [
                'key' => $key,
                'env' => $config->getConstantFromKey($key),
                'value' => $config->get($key),
                'source' => $config->getSource($key),
            ];
        }
        return new RowsOfFields($out);
    }
}
