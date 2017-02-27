<?php

namespace OrgName\PluginName\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;

class NullCommand extends TerminusCommand
{
    /**
     * Do Nothing
     *
     * @command test:null
     */
    public function doNothing()
    {
    }
}
