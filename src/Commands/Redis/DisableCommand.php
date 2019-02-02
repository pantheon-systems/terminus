<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Disables Redis add-on for a site.
     *
     * @authorize
     *
     * @command redis:disable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Disables Redis add-on for <site>.
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getRedis()->disable();
        $this->log()->notice('Redis disabled. Converging bindings.');
        $workflow = $site->converge();
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
