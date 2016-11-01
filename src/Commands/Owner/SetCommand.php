<?php

namespace Pantheon\Terminus\Commands\Owner;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

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
     *   *Promotes user mentioned to the owner. Can use UUID, Email or Full Name.
     *
     */
    public function setOwner($sitename, $owner)
    {
        $site = $this->sites->get($sitename);
        $members = $site->user_memberships;
        try {
            $member = $members->get($owner);
        } catch (TerminusException $e) {
            $this->log()->notice($e->getMessage());
            if ($e->getMessage() == "Cannot find site user with the name \"${owner}\"") {
                throw new TerminusException("The new owner must be added with \"terminus site:team:add\" before promoting");
            } else {
                throw $e;
            }
        }
        $workflow = $site->setOwner($member->id);
        $workflow->wait();
        $this->log()->notice('Promoted new owner');
    }
}
