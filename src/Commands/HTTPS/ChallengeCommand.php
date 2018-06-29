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
class ChallengeCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Writes a challenge file to the current directory (or the specified
     * location) and prints instructions on how to serve it.
     *
     * @authorize
     *
     * @command https:challenge-file
     * @aliases challenge
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $domain The domain to produce a challenge for.
     *
     * @usage <site>.<env> Displays domains associated with <site>'s <env> environment.
     */
    public function writeChallengeFile($site_env, $domain)
    {
        list(, $env) = $this->getSiteEnv($site_env);

        $domains = $env->getDomains()->fetchWithRecommendations();
        if (!$domains->has($domain)) {
            $command = "terminus domain:add $site_env $domain";
            $this->log()->notice('The domain {domain} has not been added to this site and environment. Use the command {command} to add it.', compact('domain', 'command'));
            throw new TerminusException('Cannot create challenge file.');
        }
        $domainToVerify = $domains->get($domain);

        $data = $domains->getACMEStatus($domainToVerify->id);
        $data = $data->ownership_status;
        $status = $data->status;

        if ($status == 'completed') {
            $this->log()->notice('Domain verification for {domain} has been completed.', compact('domain'));
            return;
        }

        if ($status == 'not_required') {
            $this->log()->notice('Domain verification for {domain} is not necessary; https has not been configured for this domain in its current location.', compact('domain'));
            return;
        }

        if ($status != 'required') {
            throw new TerminusException('Unimplemented status {status} for domain {domain}.', compact('status', 'domain'));
        }

        if (empty($data->verification_file_name) || empty($data->verification_file_link)) {
            throw new TerminusException('No challenge file information available for domain {domain}.', compact('status', 'domain'));
        }

        $filename = $data->verification_file_name;
        $contents = file_get_contents($data->verification_file_link);

        file_put_contents($filename, $contents);
        $this->log()->notice('Wrote ACME challenge to file {filename}', compact('filename'));
        $this->log()->notice('Please copy this file to your web server so that it will be served from the URL');
        $this->log()->notice('{url}', ['url' => "http://$domain/.well-known/acme-challenge/$filename"]);
    }
}
