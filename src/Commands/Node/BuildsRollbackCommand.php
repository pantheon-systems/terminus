<?php

namespace Pantheon\Terminus\Commands\Node;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;

/**
 * Class BuildsRollbackCommand.
 *
 * @package Pantheon\Terminus\Commands\Node
 */
class BuildsRollbackCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    /**
     * Rolls back a deployed build on a Node.js site environment.
     *
     * @authorize
     *
     * @command node:builds:rollback
     * @aliases nbr,node:build:rollback
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $build_id The build ID to roll back to
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> <build-id> Roll back <site>'s <env> environment to the specified build.
     */
    public function rollback($site_env, $build_id)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if (!$site->isEvcs()) {
            throw new TerminusException(
                'This command only works for sites using external VCS (GitHub, GitLab, Bitbucket).'
            );
        }

        if (!$site->isNodejs()) {
            throw new TerminusException(
                'This command only works for Node.js sites.'
            );
        }

        $env_name = $env->getName();

        $this->log()->notice(
            'Initiating rollback of build {build_id} on {site} environment {env}...',
            [
                'build_id' => $build_id,
                'site' => $site->getName(),
                'env' => $env_name,
            ]
        );

        $url = sprintf(
            '/api/sites/%s/environment/%s/build/%s/rollback',
            $site->get('id'),
            $env_name,
            $build_id
        );

        $this->postToUrl($url);

        $this->log()->notice(
            'Rollback successfully initiated for {site} environment {env} (build {build_id}).',
            [
                'site' => $site->getName(),
                'env' => $env_name,
                'build_id' => $build_id,
            ]
        );
    }

    /**
     * Send a POST request to a given API path.
     *
     * @param string $path API path (without protocol/host).
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    private function postToUrl(string $path): void
    {
        $protocol = $this->getConfig()->get('protocol');
        $host = $this->getConfig()->get('host');

        $url = sprintf('%s://%s%s', $protocol, $host, $path);

        $options = [
            'method' => 'POST',
            'headers' => [
                'X-Pantheon-Session' => $this->request->session()->get('session'),
            ],
        ];

        $result = $this->request()->request($url, $options);
        $statusCode = $result->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            $data = $result->getData();
            $message = 'Rollback request failed.';
            if (is_object($data) && !empty($data->error)) {
                $message = $data->error;
            } elseif (is_string($data) && !empty(trim($data))) {
                $message = trim($data);
            }
            throw new TerminusException($message);
        }
    }
}
