<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Adds a tag to a site by an organization
     *
     * @authorized
     *
     * @command tag:add
     *
     * @param string $site_name The name or UUID of a site to add the tag to
     * @param string $organization The name or UUID of an organization which will tag this site
     * @param string $tag The tag to apply to the site
     *
     * @usage terminus tag:add <site_name> <org_name> <tag>
     *    Adds a <tag> tag to the <site_name> site by the <org_name> organization
     */
    public function add($site_name, $organization, $tag)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $org->getSiteMemberships()->get($site_name)->site;
        $site->tags->create($tag);
        $this->log()->notice(
            '{org} has tagged {site} with {tag}.',
            ['org' => $org->get('profile')->name, 'site' => $site->get('name'), 'tag' => $tag,]
        );
    }
}
