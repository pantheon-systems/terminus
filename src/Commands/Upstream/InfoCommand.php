<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Upstream
 */
class InfoCommand extends TerminusCommand
{
    /**
     * Displays information about an upstream.
     *
     * @authorize
     *
     * @command upstream:info
     *
     * @param string $upstream Upstream name or UUID
     *
     * @field-labels
     *     id: ID
     *     label: Name
     *     machine_name: Machine Name
     *     type: Type
     *     framework: Framework
     *     repository_url: URL
     *     description: Description
     *     organization: Organization
     * @return PropertyList
     *
     * @usage <upstream> Displays information about the <upstream> upstream.
     */
    public function info($upstream)
    {
        return new PropertyList($this->session()->getUser()->getUpstreams()->get($upstream)->serialize());
    }
}
