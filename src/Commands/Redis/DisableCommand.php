<?php

namespace Pantheon\Terminus\Commands\Redis;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class DisableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

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
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
