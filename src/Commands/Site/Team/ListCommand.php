<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * List team members for a site.
     *
     * @command site:team:list
     *
     * @param string $site_id Site name to list team members for.
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   first: First name
     *   last: Last name
     *   email: Email
     *   role: Role
     *   uuid: User ID
     *
     * @usage terminus site:team:list my-site
     *   List team members for the site named `my-site`.
     */
    public function teamList($site_id)
    {
        $site = $this->getSite($site_id);
        $user_memberships = $site->getUserMemberships()->all();
        $data = [];
        foreach ($user_memberships as $user_membership) {
            $user = $user_membership->get('user');
            $data[] = array(
                'first' => $user->profile->firstname,
                'last'  => $user->profile->lastname,
                'email' => $user->email,
                'role'  => $user_membership->get('role'),
                'uuid'  => $user->id,
            );
        }
        return new RowsOfFields($data);
    }
}
