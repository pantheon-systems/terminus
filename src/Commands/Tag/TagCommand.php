<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class TagCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
abstract class TagCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    /**
     * @param $site_identifier
     * @param $org_id
     * @return array
     */
    protected function getModels($site_identifier, $org_id)
    {
        $site = $this->sites->get($site_identifier);
        $site_id = $site->id;

        $organization = $this->session()->getUser()->getOrganizationMemberships()->get($org_id)->getOrganization();
        $membership = $organization->getSiteMembership($site_id);
        return [$organization, $site, $membership->getTags(),];
    }
}
