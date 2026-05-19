<?php

namespace Pantheon\Terminus\Commands\Vcs\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Symfony\Component\Process\Process;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class ListCommand.
 *
 * @package Pantheon\Terminus\Commands\Vcs\Connection
 */
class ListCommand extends TerminusCommand implements RequestAwareInterface
{
    use VcsClientAwareTrait;
    use StructuredListTrait;

    /**
     * Lists connected VCS installations from the VCS API.
     *
     * @authorize
     *
     * @command vcs:connection:list
     * @aliases vcs-connection-list
     *
     * @field-labels
     *   id: Installation ID
     *   vcs_provider: VCS Provider
     *   type: Type
     *   login_name: Login name
     *   host: Host
     * @default-table-fields id,vcs_provider,type,login_name,host
     *
     * @param string $organization Organization name, label, or ID.
     *
     *
     * @usage <organization> Lists connected VCS installations from the VCS API.
     *
     * @usage Lists connected VCS installations from the VCS API.
     *
     * @return RowsOfFields
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function connectList(string $organization)
    {
        $organization = $this->session()->getUser()->getOrganizationMemberships()->get(
            $organization
        )->getOrganization();

        $installations_resp = $this->getVcsClient()->getInstallations(
            $organization->id,
            $this->session()->getUser()->id
        );
        $existing_installations_data = $installations_resp['data'] ?? [];
        $this->log()->debug(
            'Existing installations: {installations}',
            ['installations' => print_r($existing_installations_data, true)]
        );

        if (count($existing_installations_data) === 0) {
            $this->log()->info(
                'No connected VCS installations found for organization {org}.',
                ['org' => $organization->name]
            );
            return new RowsOfFields([]);
        }

        $table_data = [];
        foreach ($existing_installations_data as $installation) {
            $table_data[$installation->installation_id] = [
                'id' => $installation->installation_id,
                'vcs_provider' => $installation->alias,
                'type' => $installation->type,
                'login_name' => $installation->login_name,
                'host' => $installation->hostname ?? 'github.com',
            ];
        }

        $table = new RowsOfFields($table_data);
        return $table;
    }
}
