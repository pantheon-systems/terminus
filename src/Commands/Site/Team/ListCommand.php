<?php

namespace Pantheon\Terminus\Commands\Site\Team;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Site\Team
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the list of team members for a site.
     *
     * @authorize
     *
     * @command site:team:list
     *
     * @field-labels
     *     firstname: First name
     *     lastname: Last name
     *     email: Email
     *     role: Role
     *     id: User ID
     * @return RowsOfFields
     *
     * @param string $site_id Site name
     *
     * @usage <site> Displays the list of team members for <site>.
     */
    public function teamList($site_id)
    {
        $site = $this->getSite($site_id);
        $user_memberships = $site->getUserMemberships()->serialize();
        return new RowsOfFields($user_memberships);
    }
}
