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
     * Print information about the given upstream
     *
     * @command upstream:info
     * @aliases upstream
     *
     * @param string $upstream The name or UUID of the upstream to retrieve information on
     *
     * @field-labels
     *   id: ID
     *   longname: Name
     *   category: Category
     *   type: Type
     *   framework: Framework
     *   upstream: URL
     *   author: Author
     *   description: Description
     * @return PropertyList
     *
     * @usage terminus upstream:info <upstream>
     *    Displays information about the upstream identified by <upstream>
     */
    public function info($upstream)
    {
        return new PropertyList($this->session()->getUser()->getUpstreams()->get($upstream)->serialize());
    }
}
