<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enable Solr indexing for the a site.
     *
     * @authorized
     *
     * @command solr:enable
     *
     * @param string $site_id Name of the site to enable Solr for
     *
     * @usage terminus solr:enable my-site
     *   Enable Solr indexing for the site named 'my-site'.
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->solr->enable();
        $this->log()->notice('Solr enabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
            error_log('waiting...');
        }
        $this->log()->notice($workflow->getMessage());
    }
}
