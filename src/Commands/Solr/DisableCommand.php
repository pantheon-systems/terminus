<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\API\Site\SiteAwareInterface;
use Pantheon\Terminus\API\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

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
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
