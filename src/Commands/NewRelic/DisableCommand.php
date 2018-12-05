<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class DisableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Disables New Relic for a site.
     *
     * @authorize
     *
     * @command new-relic:disable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Disables New Relic for <site>.
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getNewRelic()->disable();
        $this->log()->notice('New Relic disabled. Converging bindings.');
        $workflow = $site->converge();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
