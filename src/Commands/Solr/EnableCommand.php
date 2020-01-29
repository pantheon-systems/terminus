<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

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
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
