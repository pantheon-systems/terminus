<?php

namespace Pantheon\Terminus\Commands\Site\Upstream;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ClearCacheCommand
 * @package Pantheon\Terminus\Commands\Site\Upstream
 */
class ClearCacheCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Clears caches for the site's codeserver.
     *
     * @authorize
     *
     * @command site:upstream:clear-cache
     * @aliases site:upstream:cc upstream:cache-clear upstream:cc
     *
     * @param string $site The name or UUID of a site
     *
     * @usage <site> Clears the code cache for <site>.
     */
    public function clearCache($site)
    {
        $site_obj = $this->sites->get($site);
        $this->processWorkflow($site_obj->getUpstream()->clearCache());
        $this->log()->notice('Code cache cleared on {site}.', ['site' => $site_obj->get('name'),]);
    }
}
