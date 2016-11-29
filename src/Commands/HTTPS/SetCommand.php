<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Add or replace an HTTPS certificate on an environment
     *
     * @authorize
     *
     * @command https:set
     * @aliases https:add https:enable
     *
     * @param string $site_env Site and environment in the form `site-name.env`.
     * @param string $certificate A file containing the ssl certificate
     * @param string $private_key A file containing the private key
     * @option string $intermediate-certificate A file containing the CA intermediate certificate(s)
     *
     * @usage https:set <site>.<env> <cert_file> <key_file>
     *    Upload the certificate from <cert_file> and key from <key_file> and apply them to the <env> environment of <site>
     * @usage https:set <site>.<env> <cert> <key> --intermediate-certificate=<int>
     *    Specify the certificate <cert>, the key <key>, and the and intermediary certificate <int> directly on the command line and apply them to the <env> environment of <site>
     */
    public function set($site_env, $certificate, $private_key, $options = ['intermediate-certificate' => null,])
    {
        list(, $env) = $this->getSiteEnv($site_env);
        $key = [
            'cert' => file_exists($certificate) ? trim(file_get_contents($certificate)) : $certificate,
            'key' => file_exists($private_key) ? trim(file_get_contents($private_key)) : $private_key,
        ];
        if (!is_null($int = $options['intermediate-certificate'])) {
            if (file_exists($int)) {
                $key['intermediary'] = trim(file_get_contents($int));
            } else {
                $key['intermediary'] = $int;
            }
        }

        // Set the key for the environment.
        $workflow = $env->setHttpsCertificate($key);

        // Wait for the workflow to complete.
        $this->log()->notice('SSL certificate updated. Converging loadbalancer.');
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
