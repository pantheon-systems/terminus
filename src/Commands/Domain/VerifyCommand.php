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
            'json' => ['challenge_type' => 'dns-01'],
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

        // Fetch the domain details to check verification status and challenge info.
        $domainUrl = sprintf(
            'sites/%s/environments/%s/domains/%s',
            $site->id,
            $env->id,
            rawurlencode($domain)
        );
        $domainResponse = $this->request()->request($domainUrl, [
            'method' => 'get',
        ]);
        $data = $domainResponse->getData();

        // Check if already verified.
        if (
            is_object($data)
            && !empty($data->ownership_status)
            && $data->ownership_status->preprovision_result->status === 'success'
        ) {
            $this->log()->notice(
                'Ownership of {domain} on {site}.{env} has been verified.',
                [
                    'domain' => $domain,
                    'site' => $site->getName(),
                    'env' => $env->getName(),
                ]
            );
            return;
        }

        // Display the DNS challenge info if available.
        $this->log()->warning(
            'Ownership of {domain} on {site}.{env} has not been verified yet.',
            [
                'domain' => $domain,
                'site' => $site->getName(),
                'env' => $env->getName(),
            ]
        );

        if (
            is_object($data)
            && !empty($data->acme_preauthorization_challenges)
            && !empty($data->acme_preauthorization_challenges->{'dns-01'})
        ) {
            $dnsChallenge = $data->acme_preauthorization_challenges->{'dns-01'};
            $this->log()->notice(
                'Add the following TXT record to your DNS provider:' . PHP_EOL . PHP_EOL
                . '  Name:  {key}' . PHP_EOL
                . '  Value: {value}' . PHP_EOL,
                [
                    'key' => $dnsChallenge->verification_key ?? '_acme-challenge.' . $domain,
                    'value' => $dnsChallenge->verification_value,
                ]
            );
            $this->log()->notice(
                'Once the TXT record is in place, re-run this command to verify.'
            );
        } else {
            $this->log()->notice(
                'Ensure your DNS TXT record is configured correctly.'
            );
        }
    }
}
