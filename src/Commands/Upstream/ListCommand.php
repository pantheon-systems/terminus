<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

class ListCommand extends TerminusCommand
{
    /**
     * Lists the upstreams your logged-in user can access
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
     */
    public function listUpstreams()
    {
        $upstreams = array_map(
            function ($upstream) {
                return $upstream->serialize();
            },
            $this->session()->getUser()->getUpstreams()->all()
        );
        return new RowsOfFields($upstreams);
    }
}
