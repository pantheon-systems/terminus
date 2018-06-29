<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class VerifyACMEChallengeCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class VerifyACMEChallengeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Attempts to verify the ACME challenge for a domain by checking the
     * https validation file or confirming the DNS txt record containing
     * the ACME challenge.
     *
     * @authorize
     *
     * @command https:verify
     * @aliases acme
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain Optional.
     * @return array
     * @format yaml
     *
     * @usage <site>.<env> <domain> Verifies the ACME challenge for <domain> in <site>'s <env> environment.
     */
    public function verifyACMEChallenge($site_env, $domain = '')
    {
        list(, $env) = $this->getSiteEnv($site_env);

        $domains = $env->getDomains()->fetchWithRecommendations();
        $domainsToVerify = $this->determineValidationDomains($domains, $domain);

        if (empty($domainsToVerify)) {
            throw new TerminusException(
                'There are no domains that require verification.'
            );
        }

        foreach ($domainsToVerify as $domainToVerify) {

            $data = $env->validateACMEChallenge($domainToVerify->id);

            // Probably we can check $data->ownership_status to determine
            // whether this verified or not.

            // If it has ['status' => 'required'] that means the domain
            // has not been verified yet.

            return (array)$data->ownership_status;
        }
    }

    protected function determineValidationDomains($domains, $domain)
    {
        if (!empty($domain)) {
            if (!$domains->has($domain)) {
                throw new TerminusException('The domain {domain} has not been added to this site and environment.', compact('domain'));
            }
            $domainToVerify = $domains->get($domain);
            if ($domainToVerify->getStatus() != 'action_required') {
                throw new TerminusException('The domain {domain} does not require verification.', compact('domain'));
            }
            return [$domainToVerify];
        }

        $result = [];
        foreach ($domains->all() as $domain) {
            if ($domain->getStatus() == 'action_required') {
                $result[] = $domain;
            }
        }
        return $result;
    }
}
