<?php

namespace Pantheon\Terminus\Commands\Secret\Org;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Commands\Secret\SecretBaseCommand;

/**
 * Class SetCommand.
 *
 * Set secret for a given org.
 *
 * @package Pantheon\Terminus\Commands\Secret\Org
 */
class SetCommand extends SecretBaseCommand
{
    /**
     * Set secret for a specific org.
     *
     * @authorize
     *
     * @command secret:org:set
     * @aliases secret-org-set
     *
     * @option string $type Secret type. Available options are env, runtime and composer.
     * @option array $scope Secret scope. Available options are ic (integrated composer), user, and web.
     *   Multiple options should be specified in comma separated format. Ex: --scope=ic,web.
     * @option string $env Environment name
     * @option boolean $debug Run command in debug mode
     *
     * @param string $org_id The name or UUID of a org to retrieve information on
     * @param string $name The secret name
     * @param string $value The secret value
     * @param array $options
     *
     * @usage <org> <name> <value> Set secret <name> with value <value>.
     * @usage <org> <name> <value> --debug Set given secret (debug mode).
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function setSecret(
        string $org_id,
        string $name,
        string $value,
        array $options = [
            'type' => null,
            'scope' => null,
            'env' => null,
            'debug' => false,
        ]
    ) {
        $org = $this->session()->getUser()->getOrganizationMemberships()->get($org_id)->getOrganization();
        if (empty($org)) {
            $this->log()->error('Either the org is unavailable or you dont have permission to access it..');
        }
        $this->setupRequest();
        $result = $this->secretsApi->setSecret(
            $org->id,
            $name,
            $value,
            $options['env'],
            $options['type'],
            $options['scope'],
            $options['debug'],
            "organizations"
        );

        if ($result->isError()) {
            $this->log()->error('An error happened when trying to set the secret.');
            throw new TerminusException($result->getData());
        }
        $this->log()->notice('Secret successfully set.');
    }
}
