<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Collections\Organizations;

/**
 * Class OrganizationsTrait
 *
 * @package Pantheon\Terminus\Friends
 */
trait OrganizationsTrait
{
    /**
     * Returns all organization members of this site
     *
     * @return Organizations
     */
    public function getOrganizations(): Organizations
    {
        $nickname = \uniqid(__FUNCTION__ . '-');
        $this->getContainer()->add(
            $nickname,
            Organizations::class
        )
            ->addArgument(
                ['data' => $this->getOrganizationMemberships()->all()]
            );

        return $this->getContainer()
            ->get($nickname);
    }
}
