<?php

namespace Pantheon\Terminus\Commands\Redis;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Redis
 */
class EnableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Enables Redis add-on for a site.
     *
     * @authorize
     *
     * @command redis:enable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Enables Redis add-on for <site>.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getRedis()->enable();
        $this->log()->notice('Redis enabled. Converging bindings.');
        $workflow = $site->converge();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
