<?php

namespace Pantheon\Terminus\Commands\Tag;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Removes a tag from a site by an organization
     *
     * @authorized
     *
     * @command tag:remove
     *
     * @param string $site_name The name or UUID of a site to remove the tag from
     * @param string $organization The name or UUID of an organization which has tagged this site
     * @param string $tag The tag to remove from the site
     *
     * @usage terminus tag:remove <site_name> <org_name> <tag>
     *    Removes the <tag> tag from the <site_name> site by the <org_name> organization
     */
    public function remove($site_name, $organization, $tag)
    {
        $org = $this->session()->getUser()->getOrgMemberships()->get($organization)->getOrganization();
        $site = $org->getSiteMemberships()->get($site_name)->site;
        $site->tags->get($tag)->delete();

        $this->log()->notice(
            '{org} has removed the {tag} tag from {site}.',
            ['org' => $org->get('profile')->name, 'tag' => $tag, 'site' => $site->get('name'),]
        );
    }
}
