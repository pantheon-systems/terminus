<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Models\TerminusModel;

/**
 * Interface UpstreamInterface
 * @package Pantheon\Terminus\Friends
 */
interface UpstreamInterface
{
    /**
     * @return [Upstream|SiteUpstream] Returns an Upstream-type object
     */
    public function getUpstream();

    /**
     * @param [Upstream|SiteUpstream] $upstream
     */
    public function setUpstream(TerminusModel $upstream);
}
