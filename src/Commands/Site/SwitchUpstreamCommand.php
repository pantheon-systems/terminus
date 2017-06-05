<?php

namespace Pantheon\Terminus\Commands\Site;

/**
 * Class SwitchUpstreamCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class SwitchUpstreamCommand extends SiteCommand
{
  /**
   * Changes a site's Upstream.
   *
   * @authorize
   *
   * @command site:switchupstream
   *
   * @param string $site_name Site name
   * @param string $upstream_id Upstream ID
   *
   * @usage <site> <upstreamid> Changes the <site> upstream to the one identified by <upstream_id>
   */
    public function switchUpstream($site_name, $upstream_id)
    {

        $site = $this->getSite($site_name);

        $this->log()->warning('This functionality is experimental. Do not use this on production sites.');
        if (!$this->confirm('Are you sure you want change the upstream for {site}?', ['site' => $site->getName()])) {
            return;
        }

        $workflow = $site->switchUpstream($upstream_id);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('Switched upstream for {site}', ['site' => $site->getName()]);
    }
}
