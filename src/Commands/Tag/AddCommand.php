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
     * Adds a tag on a site within an organization.
     *
     * @authorize
     *
     * @command tag:add
     *
     * @param string $site_name Site name
     * @param string $organization Organization name or UUID
     * @param string $tag Tag
     *
     * @usage terminus tag:add <site> <org> <tag>
     *     Adds the <tag> tag to <site> within <org>.
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
