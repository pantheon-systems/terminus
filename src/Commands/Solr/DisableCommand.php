<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DisableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disable Solr indexing on a site
     *
     * @authorize
     *
     * @command solr:disable
     *
     * @param string $site_id Name of the site to disable Solr on
     *
     * @usage <site>
     *   Disables Solr indexing on <site>
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getSolr()->disable();
        $this->log()->notice('Solr disabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
