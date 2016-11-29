<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Place a tag on a site by an organization
     *
     * @authorize
     *
     * @command tag:add
     *
     * @param string $site_name The name or UUID of a site to add the tag to
     * @param string $organization The name or UUID of an organization which will tag this site
     * @param string $tag The tag to apply to the site
     *
     * @usage terminus tag:add <site> <org> <tag>
     *    Adds a <tag> tag to the <site> site by the <org> organization
     */
    public function add($site_name, $organization, $tag)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $org->getSiteMemberships()->get($site_name)->getSite();
        $site->tags->create($tag);
        $this->log()->notice(
            '{org} has tagged {site} with {tag}.',
            ['org' => $org->get('profile')->name, 'site' => $site->get('name'), 'tag' => $tag,]
        );
    }
}
