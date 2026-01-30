<?php

namespace Pantheon\Terminus\Commands\Secret\Org;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Commands\Secret\SecretBaseCommand;

/**
 * Class DeleteCommand.
 *
 * Delete secret by name.
 *
 * @package Pantheon\Terminus\Commands\Secret\Org
 */
class DeleteCommand extends SecretBaseCommand
{
    /**
     * Delete given secret for a specific org.
     *
     * @authorize
     *
     * @command secret:org:delete
     * @aliases secret-org-delete
     *
     * @option env string The environment to delete the secret from
     * @option boolean $debug Run command in debug mode
     *
     * @param string $org_id The name or UUID of an organization to retrieve information on
     * @param string $name The secret name
     * @param array $options
     *
     * @usage <org> <name> Delete given secret.
     * @usage <org> <name> --debug Delete given secret (debug mode).
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteSecret(string $org_id, string $name, array $options = [
        'env' => null,
        'debug' => false,
    ])
    {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($org_id)->getOrganization();
        if (empty($org)) {
            $this->log()->error('Either the org is unavailable or you dont have permission to access it..');
        }
        $this->setupRequest();
        $result = $this->secretsApi->deleteSecret($org->id, $name, $options['env'], $options['debug'], "organizations");
        if ($result->isError()) {
            $this->log()->error('An error happened when trying to delete the secret.');
            throw new TerminusException($result->getData());
        }
        $success_message = 'Secret successfully deleted.';
        if (!empty($options['env'])) {
            $success_message = 'Secret environment override deleted if it existed.';
        }
        $this->log()->notice($success_message);
    }
}
