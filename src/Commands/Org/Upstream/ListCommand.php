<?php

namespace Pantheon\Terminus\Commands\Org\Upstream;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Upstream\ListCommand as UserListCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\Upstream
 */
class ListCommand extends UserListCommand
{
    /**
     * Displays the list of upstreams belonging to an organization.
     *
     * @command org:upstream:list
     * @aliases org:upstreams
     *
     * @field-labels
     *     id: ID
     *     label: Name
     *     machine_name: Machine Name
     *     category: Category
     *     type: Type
     *     framework: Framework
     * @param string $organization Organization name, label, or ID
     * @option all Show all upstreams
     * @option framework Framework filter
     * @option name Name filter
     * @return RowsOfFields
     *
     * @usage Displays the list of upstreams accessible to the currently logged-in user.
     * @usage --all Displays upstreams of all types, including product.
     * @usage --framework=<framework> Displays a list of accessible upstreams using the <framework> framework.
     * @usage --name=<regex> Displays a list of accessible upstreams with a name that matches <regex>.
     */
    public function listOrgUpstreams($organization, $options = ['all' => false, 'framework' => null, 'name' => null,])
    {
        return $this->listUpstreams(array_merge($options, ['org' => $organization,]));
    }
}
