<?php

namespace OrgName\PluginName\Commands\OptionalCommandGroup;

use Pantheon\Terminus\Commands\TerminusCommand;

class GroupNullCommand extends TerminusCommand
{
    /**
     * Do Nothing
     *
     * @command test:group:null
     */
    public function doNothing()
    {
    }
}
