<?php

namespace Pantheon\Terminus\Commands\Tag;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class AddCommand extends TagCommand
{
    /**
     * Adds a tag on a site within an organization.
     *
     * @authorize
     *
     * @command tag:add
     *
     * @param string $site_name Site name
     * @param string $organization Organization name, label, or ID
     * @param string $tag Tag
     *
     * @usage <site> <org> <tag> Adds the <tag> tag to <site> within <org>.
     */
    public function add($site_name, $organization, $tag)
    {
        list($org, $site, $tags) = $this->getModels($site_name, $organization);
        $tags->create($tag);
        $this->log()->notice(
            '{org} has tagged {site} with {tag}.',
            ['org' => $org->getName(), 'site' => $site->getName(), 'tag' => $tag,]
        );
    }
}
