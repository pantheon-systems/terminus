<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class TagCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
abstract class TagCommand extends TerminusCommand
{
    /**
     * @param $site_id
     * @param $org_id
     * @return array
     */
    protected function getModels($site_id, $org_id)
    {
        $organization = $this->session()->getUser()->getOrganizationMemberships()->get($org_id)->getOrganization();
        $membership = $organization->getSiteMemberships()->get($site_id);
        return [$organization, $membership->getSite(), $membership->getTags(),];
    }
}
