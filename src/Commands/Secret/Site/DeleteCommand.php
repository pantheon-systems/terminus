<?php

namespace Pantheon\Terminus\Commands\Secret\Site;

use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Commands\Secret\SecretBaseCommand;

/**
 * Class DeleteCommand.
 *
 * Delete secret by name.
 *
 * @package Pantheon\Terminus\Commands\Secret\Site
 */
class DeleteCommand extends SecretBaseCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Delete given secret for a specific site.
     *
     * @authorize
     *
     * @command secret:site:delete
     * @aliases secret-delete, secret:delete
     *
     * @option boolean $debug Run command in debug mode
     *
     * @param string $siteenv <site_name>, site UUID, or <site.env> for environment-specific secrets
     * @param string $name The secret name
     * @param array $options
     *
     * @usage <site[.env]> <name> Delete given secret.
     * @usage <site[.env]> <name> --debug Delete given secret (debug mode).
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function deleteSecret($siteenv, string $name, array $options = ['debug' => false])
    {
        if (strpos($siteenv, '.') !== false) {
            list($site_id, $env_name) = explode('.', $siteenv);
        } else {
            $site_id = $siteenv;
            $env_name = null;
        }

        $site = $this->getSiteById($site_id);
        $this->setupRequest();
        $result = $this->secretsApi->deleteSecret($site->id, $name, $env_name, $options['debug']);
        if ($result->isError()) {
            $this->log()->error('An error happened when trying to delete the secret.');
            throw new TerminusException($result->getData());
        }
        $success_message = 'Secret successfully deleted.';
        if ($env_name) {
            $success_message = 'Secret environment override deleted if it existed.';
        }
        $this->log()->notice($success_message);
    }
}
