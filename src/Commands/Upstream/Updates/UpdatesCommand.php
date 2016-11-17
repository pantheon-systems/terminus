<?php

namespace Pantheon\Terminus\Commands\Upstream\Updates;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

abstract class UpdatesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Return the upstream for the given site
     *
     * @param $site
     * @return object The upstream information
     * @throws TerminusException
     */
    protected function getUpstreamUpdates($site)
    {
        $upstream = $site->upstream->getUpdates();

        if (empty($upstream)) {
            $message = 'There was a problem checking your upstream status. Please try again.';
            throw new TerminusException($message);
        }
        return $upstream;
    }

    /**
     * Get the list of upstream updates for a site.
     *
     * @param $site
     * @return array The list of updates
     * @throws TerminusException
     */
    protected function getUpstreamUpdatesLog($site)
    {
        $upstream = $this->getUpstreamUpdates($site);
        if (!empty($upstream->update_log)) {
            return (array)$upstream->update_log;
        }
        return [];
    }
}
