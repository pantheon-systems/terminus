<?php

namespace Pantheon\Terminus\Commands\Tag;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the list of tags for a site within an organization.
     *
     * @authorize
     *
     * @command tag:list
     * @aliases tags
     *
     * @param string $site_name Site name
     * @param string $organization Organization name or UUID
     *
     * @return PropertyList
     *
     * @usage terminus tag:list <site> <org>
     *    Displays the list of tags for <site> within <org>.
     */
    public function listTags($site_name, $organization)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $org->getSiteMemberships()->get($site_name)->getSite();
        $tags = $site->tags->ids();
        if (empty($tags)) {
            $this->log()->notice(
                '{org} does not have any tags for {site}.',
                ['org' => $org->get('profile')->name, 'site' => $site->get('name'),]
            );
        }
        return new PropertyList($tags);
    }
}
