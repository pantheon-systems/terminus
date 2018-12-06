<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\SiteInterface;
use Pantheon\Terminus\Friends\SiteTrait;

/**
 * Class Branch
 * @package Pantheon\Terminus\Models
 */
class Branch extends TerminusModel implements SiteInterface
{
    use SiteTrait;

    const PRETTY_NAME = 'branch';

    /**
     * Deletes this branch from the site
     *
     * @return Workflow
     */
    public function delete()
    {
        return $this->getSite()->getWorkflows()->create(
            'delete_environment_branch',
            ['params' => ['environment_id' => $this->id,],]
        );
    }

    /**
     * Formats the Backup object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return ['id' => $this->id, 'sha' => $this->get('sha'),];
    }
}
