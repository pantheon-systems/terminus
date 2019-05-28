<?php

namespace Pantheon\Terminus\Commands\Site\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RepositoryInfoCommand
 * @package Pantheon\Terminus\Commands\Site\Upstream
 */
class RepositoryInfoCommand extends TerminusCommand implements SiteAwareInterface
{
    use StructuredListTrait;
    use SiteAwareTrait;

    /**
     * Displays information about the repository underlying the site's upstream.
     *
     * @authorize
     *
     * @command site:upstream:repository-info
     * @aliases site:upstream:repo-info site:upstream:repo site:repo
     *
     * @param string $site The name or UUID of a site
     *
     * @field-labels
     *      name: Name
     *      created_at: Created At
     *      description: Description
     *      disk_usage: Disk Usage
     *      fork_count: Fork Count
     *      is_fork: Is a Fork?
     *      is_private: Is Private?
     *      owner: Owner
     *      ssh_url: SSH URL
     *      updated_at: Updated At
     *      url: URL
     * @return PropertyList
     *
     * @usage <site> Displays information about the repository of <site>'s upstream.
     */
    public function repositoryInfo($site)
    {
        return $this->getPropertyList($this->sites->get($site)->getUpstream()->getRepository()->fetch());
    }
}
