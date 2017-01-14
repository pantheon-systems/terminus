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
     * Removes a tag from a site within an organization.
     *
     * @authorize
     *
     * @command tag:remove
     * @aliases tag:rm
     *
     * @param string $site_name Site name
     * @param string $organization Organization name or UUID
     * @param string $tag Tag
     *
     * @usage terminus tag:remove <site> <org> <tag>
     *     Removes the <tag> tag from <site> within <org>.
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
