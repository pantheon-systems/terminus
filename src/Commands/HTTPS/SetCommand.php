<?php

namespace Pantheon\Terminus\Commands\HTTPS;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\HTTPS
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Enables HTTPS and/or updates the SSL certificate for the environment.
     *
     * @authorize
     *
     * @command https:set
     * @aliases https:add https:enable
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $certificate File containing the SSL certificate
     * @param string $private_key File containing the private key
     * @option string $intermediate-certificate File containing the CA intermediate certificate(s)
     *
     * @usage https:set <site>.<env> <cert_file> <key_file>
     *    Enables HTTPS for <site>'s <env> environment using the SSL certificate at <cert_file> and private key at <key_file>.
     * @usage https:set <site>.<env> <cert> <key> --intermediate-certificate=<int_cert_file>
     *    Enables HTTPS for <site>'s <env> environment using the SSL certificate at <cert_file>, private key at <key_file> and intermediate certificate(s) at <int_cert_file>.
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
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
