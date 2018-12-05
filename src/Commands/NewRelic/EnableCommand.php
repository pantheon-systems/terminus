<?php

namespace Pantheon\Terminus\Commands\NewRelic;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\NewRelic
 */
class EnableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Enables New Relic for a site.
     *
     * @authorize
     *
     * @command new-relic:enable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Enables New Relic for <site>.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getNewRelic()->enable();
        $this->log()->notice('New Relic enabled. Converging bindings.');
        $workflow = $site->converge();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
