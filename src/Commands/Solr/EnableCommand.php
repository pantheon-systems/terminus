<?php

namespace Pantheon\Terminus\Commands\Solr;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class EnableCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * Enables Solr add-on for a site.
     *
     * @authorize
     *
     * @command solr:enable
     *
     * @param string $site_id Site name
     *
     * @usage <site> Enables Solr add-on for <site>.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getSolr()->enable();
        $this->log()->notice('Solr enabled. Converging bindings.');
        $workflow = $site->converge();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
