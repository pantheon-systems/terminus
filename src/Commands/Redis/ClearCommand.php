<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusProcessException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ClearCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class ClearCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Clear the Redis cache for a site environment.
     *
     * @authorize
     *
     * @command redis:clear
     *
     * @param string $site_id Site name
     *
     * @usage <site> Clear the Redis cache for <site>.
     * 
     * @throws \Pantheon\Terminus\Exceptions\TerminusProcessException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function clear($site_env)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSite();
        $env = $this->getEnv($site_env);

        $workflow = $site->getRedis()->clear($env);
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
