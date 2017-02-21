<?php

namespace Pantheon\Terminus\Commands\Tag;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class RemoveCommand extends TagCommand
{
    /**
     * Removes a tag from a site within an organization.
     *
     * @authorize
     *
     * @command tag:remove
     * @aliases tag:rm
     *
     * @param string $site_name Site name
     * @param string $organization Organization name, label, or ID
     * @param string $tag Tag
     *
     * @usage <site> <org> <tag> Removes the <tag> tag from <site> within <org>.
     */
    public function remove($site_name, $organization, $tag)
    {
        list($org, $site, $tags) = $this->getModels($site_name, $organization);
        $tags->get($tag)->delete();

        $this->log()->notice(
            '{org} has removed the {tag} tag from {site}.',
            ['org' => $org->getName(), 'tag' => $tag, 'site' => $site->getName(),]
        );
    }
}
