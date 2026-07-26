<?php

namespace Pantheon\Terminus\Commands\Vcs\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class LinkCommand.
 *
 * Links an existing VCS organization connection to a new Pantheon organization.
 *
 * @package Pantheon\Terminus\Commands\Vcs\Connection
 */
class LinkCommand extends TerminusCommand implements RequestAwareInterface
{
    use VcsClientAwareTrait;

    /**
     * Links an existing VCS organization to a new Pantheon organization.
     *
     * This command allows you to share a VCS organization connection across multiple
     * Pantheon organizations, enabling a many-to-many relationship between GitHub
     * organizations and Pantheon organizations.
     *
     * @authorize
     *
     * @command vcs:connection:link
     * @aliases vcs-connection-link
     *
     * @param string $destination_org Destination Pantheon organization name, label, or ID (where the VCS connection will be linked).
     * @option vcs-org VCS organization name (e.g., GitHub organization name). If not provided, you'll be prompted to select from available VCS organizations.
     * @option source-org Source Pantheon organization name, label, or ID that already has the VCS connection. If not provided and multiple organizations have the same VCS connection, you'll be prompted to select one.
     * @option vcs-host Hostname of a GitHub Enterprise Server instance (e.g., ghes.example.com) to disambiguate VCS organizations across different hosts.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <destination-org> --vcs-org=<vcs-org> --source-org=<source-org>
     *   Links the VCS organization to the destination Pantheon organization.
     * @usage <destination-org> --vcs-org=<vcs-org>
     *   Links the VCS organization to the destination Pantheon organization. If multiple source organizations have this VCS connection, you'll be prompted to select one.
     * @usage <destination-org> --source-org=<source-org>
     *   Lists VCS organizations from the source organization and prompts you to select one to link to the destination organization.
     * @usage <destination-org>
     *   Interactive mode: prompts for both VCS organization and source Pantheon organization.
     */
    public function connectionLink(
        string $destination_org,
        array $options = [
            'vcs-org' => null,
            'source-org' => null,
            'vcs-host' => null,
        ]
    ) {
        $user = $this->session()->getUser();
        $vcs_org = $options['vcs-org'];
        $source_org = $options['source-org'];
        $github_host = $options['vcs-host'] ?? null;

        // Get and validate destination organization
        $destination_pantheon_org = $this->getAndValidateOrganization($destination_org, 'destination');

        // Determine source organization and VCS organization
        list($source_pantheon_org, $vcs_installation) = $this->determineSourceAndVcsOrg(
            $user,
            $vcs_org,
            $source_org,
            $destination_pantheon_org,
            $github_host
        );

        // Show confirmation
        $this->log()->notice('Linking VCS organization:');
        $this->log()->notice('  VCS Organization: {vcs_org} ({vcs_type})', [
            'vcs_org' => $vcs_installation->login_name,
            'vcs_type' => $vcs_installation->alias,
        ]);
        $this->log()->notice('  Source Pantheon Org: {source_org}', ['source_org' => $source_pantheon_org->getLabel()]);
        $this->log()->notice(
            '  Destination Pantheon Org: {dest_org}',
            ['dest_org' => $destination_pantheon_org->getLabel()]
        );

        if (!$this->confirm('Do you want to proceed with linking this VCS organization?')) {
            $this->log()->warning('Operation cancelled.');
            return;
        }

        // Call the API to link the VCS organization
        $this->linkVcsOrganization(
            $source_pantheon_org->id,
            $destination_pantheon_org->id,
            $vcs_installation->installation_id
        );

        $this->log()->notice('Successfully linked VCS organization {vcs_org} to {dest_org}.', [
            'vcs_org' => $vcs_installation->login_name,
            'dest_org' => $destination_pantheon_org->getLabel(),
        ]);
    }

    /**
     * Gets and validates that the organization exists and user is a member.
     *
     * @param string $org_identifier Organization name, label, or ID.
     * @param string $org_type Type of organization (for error messages).
     * @return \Pantheon\Terminus\Models\Organization
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getAndValidateOrganization(string $org_identifier, string $org_type)
    {
        try {
            $membership = $this->session()->getUser()->getOrganizationMemberships()->get($org_identifier);
            return $membership->getOrganization();
        } catch (\Exception $e) {
            throw new TerminusException(
                'Could not find {org_type} organization "{org}". Please ensure you are a member of this organization.',
                [
                    'org_type' => $org_type,
                    'org' => $org_identifier,
                ]
            );
        }
    }

    /**
     * Determines source organization and VCS organization based on provided options.
     *
     * @param \Pantheon\Terminus\Models\User $user
     * @param string|null $vcs_org
     * @param string|null $source_org
     * @param \Pantheon\Terminus\Models\Organization $destination_org
     * @return array [$source_organization, $vcs_installation]
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function determineSourceAndVcsOrg(
        $user,
        $vcs_org,
        $source_org,
        $destination_org,
        ?string $github_host = null
    ) {
        // Case 1: Both VCS org and source org are provided
        if ($vcs_org && $source_org) {
            $source_pantheon_org = $this->getAndValidateOrganization($source_org, 'source');
            $vcs_installation = $this->findVcsOrgInPantheonOrg($user, $source_pantheon_org, $vcs_org, $github_host);

            if (!$vcs_installation) {
                if ($github_host) {
                    throw new TerminusException(
                        'VCS organization "{vcs_org}" on host "{vcs_host}" not found in'
                            . ' source Pantheon organization "{source_org}".'
                            . ' Register the host first using: vcs:github-host:add {vcs_host}',
                        [
                            'vcs_org' => $vcs_org,
                            'vcs_host' => $github_host,
                            'source_org' => $source_pantheon_org->getLabel(),
                        ]
                    );
                }
                throw new TerminusException(
                    'VCS organization "{vcs_org}" not found in source Pantheon organization "{source_org}".',
                    [
                        'vcs_org' => $vcs_org,
                        'source_org' => $source_pantheon_org->getLabel(),
                    ]
                );
            }

            return [$source_pantheon_org, $vcs_installation];
        }

        // Case 2: Only VCS org is provided - find which Pantheon org has it
        if ($vcs_org && !$source_org) {
            return $this->findPantheonOrgWithVcsOrg($user, $vcs_org, $destination_org, $github_host);
        }

        // Case 3: Only source org is provided - list VCS orgs and prompt
        if (!$vcs_org && $source_org) {
            $source_pantheon_org = $this->getAndValidateOrganization($source_org, 'source');
            $vcs_installation = $this->promptForVcsOrgFromPantheonOrg($user, $source_pantheon_org);

            return [$source_pantheon_org, $vcs_installation];
        }

        // Case 4: Neither provided - fully interactive mode
        // First, get all Pantheon orgs that have VCS connections
        $orgs_with_vcs = $this->getAllOrgsWithVcsConnections($user, $destination_org);

        if (empty($orgs_with_vcs)) {
            throw new TerminusException(
                'No Pantheon organizations found with VCS connections.'
                    . ' Please use vcs:connection:add to add a VCS connection first.'
            );
        }

        // Prompt user to select source org
        $source_pantheon_org = $this->promptForSourceOrg($orgs_with_vcs);

        // Prompt user to select VCS org from the source org
        $vcs_installation = $this->promptForVcsOrgFromPantheonOrg($user, $source_pantheon_org);

        return [$source_pantheon_org, $vcs_installation];
    }

    /**
     * Finds a specific VCS organization in a Pantheon organization.
     *
     * @param \Pantheon\Terminus\Models\User $user
     * @param \Pantheon\Terminus\Models\Organization $pantheon_org
     * @param string $vcs_org_name
     * @return object|null The VCS installation object or null if not found
     */
    protected function findVcsOrgInPantheonOrg($user, $pantheon_org, $vcs_org_name, ?string $github_host = null)
    {
        $installations_resp = $this->getVcsClient()->getInstallations($pantheon_org->id, $user->id);
        $installations = $installations_resp['data'] ?? [];

        foreach ($installations as $installation) {
            if ($installation->login_name !== $vcs_org_name) {
                continue;
            }
            if ($github_host !== null) {
                $inst_hostname = $installation->hostname ?? 'github.com';
                if ($inst_hostname !== $github_host) {
                    continue;
                }
            }
            return $installation;
        }

        return null;
    }

    /**
     * Finds which Pantheon organization has a specific VCS organization.
     *
     * @param \Pantheon\Terminus\Models\User $user
     * @param string $vcs_org_name
     * @param \Pantheon\Terminus\Models\Organization $destination_org
     * @return array [$source_organization, $vcs_installation]
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function findPantheonOrgWithVcsOrg($user, $vcs_org_name, $destination_org, ?string $github_host = null)
    {
        $matching_orgs = [];
        $orgs = $user->getOrganizationMemberships()->all();

        foreach ($orgs as $membership) {
            $org = $membership->getOrganization();

            // Skip the destination org
            if ($org->id === $destination_org->id) {
                continue;
            }

            $vcs_installation = $this->findVcsOrgInPantheonOrg($user, $org, $vcs_org_name, $github_host);

            if ($vcs_installation) {
                $matching_orgs[] = [
                    'org' => $org,
                    'installation' => $vcs_installation,
                ];
            }
        }

        if (empty($matching_orgs)) {
            throw new TerminusException(
                'VCS organization "{vcs_org}" not found in any of your Pantheon organizations.',
                ['vcs_org' => $vcs_org_name]
            );
        }

        // If only one match, use it
        if (count($matching_orgs) === 1) {
            return [$matching_orgs[0]['org'], $matching_orgs[0]['installation']];
        }

        // Multiple matches - prompt user to select
        $this->log()->notice(
            'VCS organization "{vcs_org}" found in multiple Pantheon organizations:',
            ['vcs_org' => $vcs_org_name]
        );

        $org_choices = [];
        foreach ($matching_orgs as $idx => $match) {
            $org_choices[$idx] = $match['org']->getLabel() . ' (' . $match['org']->id . ')';
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion(
            'Please select the source Pantheon organization:',
            $org_choices
        );
        $question->setErrorMessage('Invalid selection.');

        $input = $this->input();
        $output = $this->output();
        $selected_idx = $helper->ask($input, $output, $question);

        // Find the index of the selected choice
        $selected_key = array_search($selected_idx, $org_choices);

        return [$matching_orgs[$selected_key]['org'], $matching_orgs[$selected_key]['installation']];
    }

    /**
     * Prompts user to select a VCS organization from a Pantheon organization.
     *
     * @param \Pantheon\Terminus\Models\User $user
     * @param \Pantheon\Terminus\Models\Organization $pantheon_org
     * @return object The selected VCS installation
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function promptForVcsOrgFromPantheonOrg($user, $pantheon_org)
    {
        $installations_resp = $this->getVcsClient()->getInstallations($pantheon_org->id, $user->id);
        $installations = $installations_resp['data'] ?? [];

        if (empty($installations)) {
            throw new TerminusException(
                'No VCS connections found in Pantheon organization "{org}".',
                ['org' => $pantheon_org->getLabel()]
            );
        }

        if (count($installations) === 1) {
            return $installations[0];
        }

        // Multiple VCS orgs - prompt user to select
        $vcs_choices = [];
        foreach ($installations as $idx => $installation) {
            $hostname = $installation->hostname ?? 'github.com';
            $host_suffix = ($hostname !== 'github.com') ? sprintf(' @ %s', $hostname) : '';
            $vcs_choices[$idx] = sprintf(
                '%s (%s) - ID: %s%s',
                $installation->login_name,
                $installation->alias,
                $installation->installation_id,
                $host_suffix
            );
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion(
            'Please select the VCS organization to link:',
            $vcs_choices
        );
        $question->setErrorMessage('Invalid selection.');

        $input = $this->input();
        $output = $this->output();
        $selected = $helper->ask($input, $output, $question);

        // Find the index of the selected choice
        $selected_key = array_search($selected, $vcs_choices);

        return $installations[$selected_key];
    }

    /**
     * Gets all Pantheon organizations that have VCS connections.
     *
     * @param \Pantheon\Terminus\Models\User $user
     * @param \Pantheon\Terminus\Models\Organization $destination_org
     * @return array Array of organizations with VCS connections
     */
    protected function getAllOrgsWithVcsConnections($user, $destination_org)
    {
        $orgs_with_vcs = [];
        $orgs = $user->getOrganizationMemberships()->all();

        foreach ($orgs as $membership) {
            $org = $membership->getOrganization();

            // Skip the destination org
            if ($org->id === $destination_org->id) {
                continue;
            }

            try {
                $installations_resp = $this->getVcsClient()->getInstallations($org->id, $user->id);
                $installations = $installations_resp['data'] ?? [];

                if (!empty($installations)) {
                    $orgs_with_vcs[] = $org;
                }
            } catch (\Exception $e) {
                // Skip orgs where we can't fetch installations
                $this->log()->debug('Could not fetch VCS installations for org {org}: {error}', [
                    'org' => $org->getLabel(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $orgs_with_vcs;
    }

    /**
     * Prompts user to select a source Pantheon organization.
     *
     * @param array $orgs Array of Pantheon organizations
     * @return \Pantheon\Terminus\Models\Organization
     */
    protected function promptForSourceOrg(array $orgs)
    {
        if (count($orgs) === 1) {
            return $orgs[0];
        }

        $org_choices = [];
        foreach ($orgs as $idx => $org) {
            $org_choices[$idx] = $org->getLabel() . ' (' . $org->id . ')';
        }

        $helper = new QuestionHelper();
        $question = new ChoiceQuestion(
            'Please select the source Pantheon organization:',
            $org_choices
        );
        $question->setErrorMessage('Invalid selection.');

        $input = $this->input();
        $output = $this->output();
        $selected = $helper->ask($input, $output, $question);

        // Find the index of the selected choice
        $selected_key = array_search($selected, $org_choices);

        return $orgs[$selected_key];
    }

    /**
     * Calls the API to link a VCS organization to a destination Pantheon organization.
     *
     * @param string $source_org_id Source Pantheon organization UUID
     * @param string $destination_org_id Destination Pantheon organization UUID
     * @param string $installation_id VCS installation ID
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function linkVcsOrganization(
        string $source_org_id,
        string $destination_org_id,
        string $installation_id
    ) {
        try {
            $result = $this->getVcsClient()->linkInstallation(
                $source_org_id,
                $destination_org_id,
                $installation_id
            );

            if (!empty($result['error'])) {
                throw new TerminusException(
                    'Error linking VCS organization: {error}',
                    ['error' => $result['error']]
                );
            }
        } catch (\Exception $e) {
            throw new TerminusException(
                'Failed to link VCS organization: {error}',
                ['error' => $e->getMessage()]
            );
        }
    }
}
