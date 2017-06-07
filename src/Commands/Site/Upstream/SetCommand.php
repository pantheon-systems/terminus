<?php

namespace Pantheon\Terminus\Commands\Site\Upstream;

use Pantheon\Terminus\Commands\Site\SiteCommand;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class SetCommand extends SiteCommand
{
  /**
   * Changes a site's upstream.
   *
   * @authorize
   *
   * @command site:upstream:set
   *
   * @param string $site_name Site name
   * @param string $upstream_id Upstream name or UUID
   *
   * @usage <site> <upstream_id> Updates <site>'s upstream to <upstream_id>.
   */
    public function set($site_name, $upstream_id)
    {
        $site = $this->getSite($site_name);
        $upstream = $this->session()->getUser()->getUpstreams()->get($upstream_id);
        $msg_params = ['site' => $site->getName(), 'upstream' => $upstream->get('longname'),];

        $this->log()->warning('This functionality is experimental. Do not use this on production sites.');
        if (!$this->confirm('Are you sure you want change the upstream for {site} to {upstream}?', $msg_params)) {
            return;
        }

        $workflow = $site->setUpstream($upstream->id);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice('Set upstream for {site} to {upstream}', $msg_params);
    }
}
