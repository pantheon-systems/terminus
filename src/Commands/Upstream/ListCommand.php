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
     * List the upstreams your logged-in user can access
     *
     * @command upstream:list
     * @aliases upstreams
     *
     * @field-labels
     *   id: ID
     *   longname: Name
     *   category: Category
     *   type: Type
     *   framework: Framework
     * @return RowsOfFields
     *
     * @usage terminus upstream:list
     *    Lists all the upstreams your logged-in user can access
     */
    public function listUpstreams()
    {
        return new RowsOfFields($this->session()->getUser()->getUpstreams()->serialize());
    }
}
