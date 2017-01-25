<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Upstream
 */
class ListCommand extends TerminusCommand
{
    /**
     * Displays the list of upstreams accessible to the currently logged-in user.
     *
     * @command upstream:list
     * @aliases upstreams
     *
     * @field-labels
     *     id: ID
     *     longname: Name
     *     category: Category
     *     type: Type
     *     framework: Framework
     * @return RowsOfFields
     *
     * @usage Displays the list of upstreams accessible to the currently logged-in user.
     */
    public function listUpstreams()
    {
        return new RowsOfFields($this->session()->getUser()->getUpstreams()->serialize());
    }
}
