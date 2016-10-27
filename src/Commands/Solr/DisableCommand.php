<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DisableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Disable Solr indexing for the a site.
     *
     * @authorized
     *
     * @command solr:disable
     *
     * @param string $site_id Name of the site to disable Solr for
     *
     * @usage terminus solr:disable my-site
     *   Disable Solr indexing for the site named 'my-site'.
     */
    public function disable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->solr->disable();
        $this->log()->notice('Solr disabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
