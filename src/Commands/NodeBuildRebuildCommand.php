<?php

namespace Pantheon\Terminus\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\VcsApi\VcsClientAwareTrait;

/**
 * Class NodeBuildRebuildCommand.
 *
 * @package Pantheon\Terminus\Commands
 */
class NodeBuildRebuildCommand extends TerminusCommand implements SiteAwareInterface, RequestAwareInterface
{
    use SiteAwareTrait;
    use VcsClientAwareTrait;

    /**
     * Rebuilds code for a Node.js site environment, optionally from a specific commit.
     *
     * @authorize
     *
     * @command node:builds:rebuild
     * @aliases nrb,node:build:rebuild
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $commit Specific commit hash to rebuild (optional, rebuilds latest if omitted)
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> Rebuild the latest code for <site>'s <env> environment.
     * @usage <site>.<env> --commit=<hash> Rebuild <site>'s <env> environment from a specific commit.
     */
    public function rebuild(
        $site_env,
        $options = ['commit' => null]
    ) {
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

        $commit_id = $options['commit'] ?? null;
        $env_name = $env->getName();

        $this->log()->notice(
            'Triggering rebuild for {site} environment {env}' . ($commit_id ? ' from commit {commit}' : ' from latest commit') . '...',
            [
                'site' => $site->getName(),
                'env' => $env_name,
                'commit' => $commit_id,
            ]
        );

        try {
            $response = $this->getVcsClient()->rebuild($site->get('id'), $env_name, $commit_id);

            if (isset($response['error'])) {
                throw new TerminusException(
                    'Failed to rebuild {site} environment {env}: {error}',
                    [
                        'site' => $site->getName(),
                        'env' => $env_name,
                        'error' => $response['error'],
                    ]
                );
            }

            $this->log()->notice(
                'Rebuild successfully triggered for {site} environment {env}.',
                [
                    'site' => $site->getName(),
                    'env' => $env_name,
                ]
            );

            if ($commit_id) {
                $this->log()->info(
                    'Rebuilding from commit: {commit}',
                    ['commit' => $commit_id]
                );
            } else {
                $this->log()->info('Rebuilding from latest commit.');
            }
        } catch (TerminusException $e) {
            if (strpos($e->getMessage(), '404') !== false && $commit_id) {
                throw new TerminusException(
                    'Commit {commit} not found for {site} environment {env}. Please verify the commit exists in the repository.',
                    [
                        'commit' => $commit_id,
                        'site' => $site->getName(),
                        'env' => $env_name,
                    ]
                );
            }
            throw $e;
        }
    }
}
