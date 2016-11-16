<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;

class InfoCommand extends TerminusCommand
{
    /**
     * Gives information about the asked-about upstream
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
     *
     * @return PropertyList
     */
    public function info($upstream)
    {
        return new PropertyList($this->session()->getUser()->getUpstreams()->get($upstream)->serialize());
    }
}
