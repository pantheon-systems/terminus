<?php

namespace Pantheon\Terminus\Commands\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\StructuredListTrait;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class RepositoryInfoCommand
 * @package Pantheon\Terminus\Commands\Upstream
 */
class RepositoryInfoCommand extends TerminusCommand
{
    use StructuredListTrait;

    /**
     * Displays information about the repository underlying this upstream.
     *
     * @command upstream:repository-info
     * @aliases upstream:repo-info upstream:repo
     *
     * @param string $upstream Upstream name or UUID
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
     * @usage <upstream> Displays information about <upstream>'s repository.
     */
    public function repositoryInfo($upstream)
    {
        return $this->getPropertyList(
            $this->session()->getUser()->getUpstreams()->get($upstream)->fetch()->getRepository()->fetch()
        );
    }
}
