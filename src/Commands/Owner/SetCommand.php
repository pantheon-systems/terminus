<?php

namespace Pantheon\Terminus\Commands\Owner;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusNotFoundException;

class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Changes owner of a site
     *
     * @command owner:set
     * @authorized
     *
     * @param string $site_name The name or UUID of a site to assign a new owner to
     * @param string $owner The email of the user to set as the new owner
     *
     * @usage terminus owner:set <site> <new_owner>
     *    Promotes user mentioned to the owner. Can use UUID, email or full name.
     */
    public function setOwner($site_name, $owner)
    {
        $site = $this->sites->get($site_name);
        try {
            $user = $site->getUserMemberships()->get($owner)->user;
        } catch (TerminusNotFoundException $e) {
            throw new TerminusNotFoundException(
                'The new owner must be added with "terminus site:team:add" before promoting.'
            );
        }
        $site->setOwner($user->id)->wait();
        $this->log()->notice(
            'Promoted {user} to owner of {site}',
            ['user' => $user->get('profile')->full_name, 'site' => $site->get('name'),]
        );
    }
}
