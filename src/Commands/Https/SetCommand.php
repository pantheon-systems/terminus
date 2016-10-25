<?php

namespace Pantheon\Terminus\Commands\Https;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Add/replace an HTTPS certificate for an environment
     *
     * @authorized
     *
     * @command https:set
     * @aliases https:add https:enable
     *
     * @param string $site_env
     *  Site and environment in the form `site-name.env`.
     * @option file $certificate
     *  A file containing the ssl certificate
     * @option file $private-key
     *  A file containing the private key
     * @option file $intermediate-certificate
     *  A file containing the CA intermediate certificate(s)
     *
     * @usage https:set awesome-site.dev --certificate=~/mycert.cert --private-key=~/privatekey.key
     *   Upload the certificate and key from the specified files.
     * @usage https:set awesome-site.dev --certificate=<cert> --private-key=<key> --intermedite-certificate=<int>
     *   Specify the certificate, key and intermediary directly on the command line.
     */
    public function set(
        $site_env,
        $options = ['certificate' => '', 'private-key' => '', 'intermediate-certificate' => '']
    ) {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');

        $args = ['certificate' => 'cert', 'private-key' => 'key', 'intermediate-certificate' => 'intermediary'];
        $key = [];
        foreach ($args as $from => $to) {
            // Read the key from the specified file if it exists.
            if ($options[$from] && file_exists($options[$from])) {
                $key[$to] = trim(file_get_contents($options[$from]));
            } // Otherwise assume that the key was passed directly.
            else {
                $key[$to] = $options[$from];
            }
        }
        $key = array_filter($key);

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
