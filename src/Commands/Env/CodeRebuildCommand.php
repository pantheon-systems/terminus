<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CodeRebuildCommand.
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class CodeRebuildCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Moves code to the specified environment's runtime from the associated git branch, retriggering Composer builds for sites using Integrated Composer. (Not applicable for Test and Live environments which run on git tags made from the Dev environment's git history.)
     *
     * @authorize
     * @interact
     *
     * @command env:code-rebuild
     * @aliases code-rebuild
     *
     * @param string $site_env Site & environment in the format `site-name.env` (only Dev or Multidev)
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> Sync code into the <site>'s Dev or multidev environment.
     */
    public function rebuild(
        $site_env
    ) {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $env = $this->getEnv($site_env);

        if ($site->isEvcs()) {
            if (($env->getName() === 'test' || $env->getName() === 'live') && !$site->isNodejs()) {
                // Rebuilding for test/live is only supported for Node.js sites.
                throw new TerminusException('Rebuilding for test/live is only supported for Node.js sites.');
            }
            return $this->rebuildFromVcs($site->get('id'), $env->getName());
        }

        if ($env->getName() === 'test' || $env->getName() === 'live') {
            throw new TerminusException('Test and live are not valid environments for this command.');
        }

        $params = [
            'converge' => true,
            'build_steps' => [
                'artifact_install' => true,
            ],
        ];

        $workflow = $env->syncCode($params);

        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }

    /**
     * Rebuild from latest vcs event.
     */
    protected function rebuildFromVcs(string $site_id, string $env)
    {
        $path = sprintf("/vcs/v1/site-details/%s/environments/%s/rebuild", $site_id, $env);
        $response = $this->request()->request($path, [
            'method' => 'POST',
            'json' => [],
        ]);
        if ($response->getStatusCode() !== 200) {
            throw new TerminusException(
                'Failed to rebuild from VCS for site {site} environment {env}. Response: {response}. Status Code: {status_code}',
                ['site' => $site_id, 'env' => $env, 'response' => $response->getBody(), 'status_code' => $response->getStatusCode()]
            );
        }
        $this->log()->info("Rebuild is now happening for site {site} environment {env}.", ['site' => $site_id, 'env' => $env]);
    }
}
