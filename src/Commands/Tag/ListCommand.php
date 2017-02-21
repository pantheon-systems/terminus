<?php

namespace Pantheon\Terminus\Commands\Tag;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Tag
 */
class ListCommand extends TagCommand
{
    /**
     * Displays the list of tags for a site within an organization.
     *
     * @authorize
     *
     * @command tag:list
     * @aliases tags
     *
     * @param string $site_name Site name
     * @param string $organization Organization name, label, or ID
     *
     * @return PropertyList
     *
     * @usage <site> <org> Displays the list of tags for <site> within <org>.
     */
    public function listTags($site_name, $organization)
    {
        list($org, $site, $tags) = $this->getModels($site_name, $organization);
        $tag_list = $tags->ids();
        if (empty($tag_list)) {
            $this->log()->notice(
                '{org} does not have any tags for {site}.',
                ['org' => $org->getName(), 'site' => $site->getName(),]
            );
        }
        return new PropertyList($tag_list);
    }
}
