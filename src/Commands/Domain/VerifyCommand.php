<?php

namespace Pantheon\Terminus\Commands\Domain;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class VerifyCommand.
 *
 * @package Pantheon\Terminus\Commands\Domain
 */
class VerifyCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    /**
     * Verifies ownership of a domain attached to an environment.
     *
     * @authorize
     * @interact
     *
     * @command domain:verify
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain Domain e.g. `example.com`
     *
     * @usage <site>.<env> <domain_name> Verifies ownership of <domain_name> on <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function verify($site_env, $domain)
    {
        $env = $this->getEnv($site_env);
        $site = $this->getSiteById($site_env);

        $url = sprintf(
            'sites/%s/environments/%s/hostnames/%s/verify-ownership',
            $site->id,
            $env->id,
            rawurlencode($domain)
        );

        $response = $this->request()->request($url, [
            'method' => 'POST',
            'json' => new \stdClass(),
        ]);

        if ($response->isError()) {
            throw new TerminusException(
                'Ownership verification failed for {domain} on {site}.{env}.',
                [
                    'domain' => $domain,
                    'site' => $site->getName(),
                    'env' => $env->getName(),
                ]
            );
        }

        $data = $response->getData();

        if (is_object($data) && isset($data->verified) && $data->verified) {
            $this->log()->notice(
                'Ownership of {domain} on {site}.{env} has been verified.',
                [
                    'domain' => $domain,
                    'site' => $site->getName(),
                    'env' => $env->getName(),
                ]
            );
        } else {
            $this->log()->warning(
                'Ownership of {domain} on {site}.{env} has not been verified yet. '
                . 'Ensure your DNS TXT record is configured correctly.',
                [
                    'domain' => $domain,
                    'site' => $site->getName(),
                    'env' => $env->getName(),
                ]
            );
        }
    }
}
