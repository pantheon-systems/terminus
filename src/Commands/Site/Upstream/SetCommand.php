<?php

namespace Pantheon\Terminus\Commands\Site\Upstream;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\Site\SiteCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class SetCommand extends SiteCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Changes a site's upstream.
     *
     * @authorize
     *
     * @command site:upstream:set
     *
     * @param string $site_name Site name
     * @param string $upstream_id Upstream name or UUID
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     *
     * @usage <site> <upstream_id> Updates <site>'s upstream to <upstream_id>.
     */
    public function set($site_name, $upstream_id)
    {
        $site = $this->getSite($site_name);
        if (!$site->getAuthorizations()->can('update_site_setting')) {
            throw new TerminusException('You do not have permission to change the upstream of this site.');
        }

        $upstream = $this->session()->getUser()->getUpstreams()->get($upstream_id);
        $msg_params = ['site' => $site->getName(), 'upstream' => $upstream->get('label'),];

        if (!$this->confirm('Are you sure you want change the upstream for {site} to {upstream}?', $msg_params)) {
            return;
        }
        $previous_upstream_id = $site->getUpstream()->id;
        if ($previous_upstream_id) {
            $this->log()->info(
                'To undo this change run `terminus site:upstream:set {site} {upstream}`',
                ['site' => $site->id, 'upstream' => $previous_upstream_id,]
            );
        }

        $workflow = $site->setUpstream($upstream->id);
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice('Set upstream for {site} to {upstream}', $msg_params);
    }
}
