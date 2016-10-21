<?php

namespace Pantheon\Terminus\Commands\Owner;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    /**
     * Changes Owner of a Site
     *
     * @command set
     * @aliases owner:set
     * @authorized
     *
     * @param string $sitename Name|UUID of a site to look up
     * @param string $owner The email of the user to set as the new owner
     * @usage terminus owner:set <site> <new_owner_email>
     */
    public function setOwner($sitename, $owner)
    {
        $site = $this->sites->get($sitename);
        $workflow = $site->setOwner($owner);
        $workflow->wait();
        $this->log()->notice('Promoted new owner');
    }
}
