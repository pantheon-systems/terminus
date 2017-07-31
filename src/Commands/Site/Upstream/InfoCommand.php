<?php

namespace Pantheon\Terminus\Commands\Site\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Site\SiteCommand;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class InfoCommand extends SiteCommand
{
  /**
   * Displays information about a site's upstream.
   *
   * @authorize
   *
   * @command site:upstream:info
   *
   * @param string $site_name Site name
   *
   * @field-labels
   *     id: ID
   *     longname: Name
   *     category: Category
   *     type: Type
   *     framework: Framework
   *     upstream: URL
   *     author: Author
   *     description: Description
   * @return PropertyList
   *
   * @usage <site_name> Displays information about <site_name>'s upstream.
   */
    public function info($site_name)
    {
        $upstream = $this->getSite($site_name)->getUpstream();
        return new PropertyList($this->session()->getUser()->getUpstreams()->get($upstream->id)->serialize());
    }
}
