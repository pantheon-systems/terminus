<?php

namespace Pantheon\Terminus\Commands\Solr;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class EnableCommand
 * @package Pantheon\Terminus\Commands\Solr
 */
class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Enable Solr indexing on a site
     *
     * @authorize
     *
     * @command solr:enable
     *
     * @param string $site_id Name of the site to enable Solr on
     *
     * @usage terminus solr:enable <site>
     *   Enables Solr indexing for <site>
     */
    public function enable($site_id)
    {
        $site = $this->getSite($site_id);
        $site->getSolr()->enable();
        $this->log()->notice('Solr enabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
