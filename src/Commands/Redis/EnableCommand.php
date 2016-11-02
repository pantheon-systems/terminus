<?php

namespace Pantheon\Terminus\Commands\Redis;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class EnableCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    
    public function enableRedis($site_id)
    {
        $site = $this->getSite($site_id);
        $site->redis->enable();
        $this->log()->notice('Redis enabled. Converging bindings.');
        $workflow = $site->converge();
        // Wait for the workflow to complete.
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice($workflow->getMessage());
    }
}
