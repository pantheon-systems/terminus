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
     * List the tags which an organization has added to a site
     *
     * @authorize
     *
     * @command tag:list
     * @aliases tags
     *
     * @param string $site_name The name or UUID of a site to list the tags of
     * @param string $organization The name or UUID of an organization which has tagged this site
     *
     * @return PropertyList
     *
     * @usage <site> <org>
     *    Lists the tags which the <org> organization has added to the <site> site
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
