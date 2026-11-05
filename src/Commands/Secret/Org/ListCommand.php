<?php

namespace Pantheon\Terminus\Commands\Secret\Org;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Commands\Secret\SecretBaseCommand;

/**
 * Class ListCommand.
 *
 * List secrets for a given org.
 *
 * @package Pantheon\Terminus\Commands\Secret\Org
 */
class ListCommand extends SecretBaseCommand
{
    /**
     * Lists secrets for a specific org.
     *
     * @authorize
     * @filter-output
     *
     * @command secret:org:list
     * @aliases secret-org-list
     *
     * @field-labels
     *   name: Secret name
     *   type: Secret type
     *   value: Secret value
     *   scopes: Secret scopes
     *   env-values: Environment override values
     *
     * @option boolean $debug Run command in debug mode
     *
     * @usage <org> Lists all secrets for current org.
     * @usage <org> --debug List all secrets for current org (debug mode).
     *
     * @param string $org_id The name or UUID of a org to retrieve information on
     * @param array $options
     *
     * @return Consolidation\OutputFormatters\StructuredData\RowsOfFields
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function listSecrets(string $org_id, array $options = ['debug' => false,])
    {
        try {
            $org = $this->session()->getUser()->getOrganizationMemberships()->get($org_id)->getOrganization();
            if (empty($org)) {
                throw new TerminusException('Either the org is unavailable or you dont have permission to access it.');
            }
        } catch (TerminusNotFoundException $e) {
            // Catch the exception just to throw it again with a more human friendly message.
            throw new TerminusException('Either the org is unavailable or you dont have permission to access it.');
        }
        $this->setupRequest();
        $result = $this->secretsApi->listSecrets($org->id, $options['debug'], "organizations");
        if ($result instanceof RequestOperationResult) {
            throw new TerminusException($result->getData());
        }
        $print_options = [
            'message' => 'You have no Secrets.'
        ];
        return $this->getTableFromData($result, $print_options);
    }
}
