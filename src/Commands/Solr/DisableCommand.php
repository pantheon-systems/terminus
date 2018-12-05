<?php

namespace Pantheon\Terminus\Commands\Solr;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class DisableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Disables Solr add-on for a site.
     *
     * @authorize
     *
     * @command solr:disable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Disables Solr add-on for <site>.
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getSolr()->disable();
        $this->log()->notice('Solr disabled. Converging bindings.');
        $workflow = $site->converge();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
