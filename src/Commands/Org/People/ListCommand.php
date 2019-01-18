<?php

namespace Pantheon\Terminus\Commands\Org\People;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\StructuredListTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Org\People
 */
class ListCommand extends TerminusCommand
{
    use StructuredListTrait;

    /**
     * Displays the list of users associated with an organization.
     *
     * @authorize
     *
     * @command org:people:list
     * @aliases org:ppl:list
     *
     * @field-labels
     *     id: ID
     *     firstname: First Name
     *     lastname: Last Name
     *     email: Email
     *     role: Role
     * @return RowsOfFields
     *
     * @param string $organization Organization name, label, or ID
     *
     * @usage <organization> Displays the list of users associated with <organization>.
     */
    public function listPeople($organization)
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($organization)->getOrganization();
        return $this->getRowsOfFields(
            $org->getUserMemberships(),
            [
                'message' => '{org} has no members.',
                'message_options' => ['org' => $org->getName(),],
            ]
        );
    }
}
