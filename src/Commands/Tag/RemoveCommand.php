<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Remove a tag placed on a site by an organization
     *
     * @authorize
     *
     * @command tag:remove
     * @aliases tag:rm
     *
     * @param string $site_name The name or UUID of a site to remove the tag from
     * @param string $organization The name or UUID of an organization which has tagged this site
     * @param string $tag The tag to remove from the site
     *
     * @usage terminus tag:remove <site> <org> <tag>
     *    Removes the <tag> tag from the <site> site by the <org> organization
     */
    public function remove($site_name, $organization, $tag)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $org->getSiteMemberships()->get($site_name)->getSite();
        $site->tags->get($tag)->delete();

        $this->log()->notice(
            '{org} has removed the {tag} tag from {site}.',
            ['org' => $org->get('profile')->name, 'tag' => $tag, 'site' => $site->get('name'),]
        );
    }
}
