<?php

namespace Pantheon\Terminus\Models;

/**
 * Class UpstreamStatus
 * @package Pantheon\Terminus\Models
 */
class UpstreamStatus extends TerminusModel
{
    /**
     * @var Environment
     */
    public $environment;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        if (isset($options['environment'])) {
            $this->environment = $options['environment'];
        }
    }

    /**
     * Returns the status of this site's upstream updates
     *
     * @return string $status 'outdated' or 'current'
     */
    public function getStatus()
    {
        return $this->hasUpdates() ? 'outdated' : 'current';
    }

    /**
     * Retrives upstream updates
     *
     * @return object
     */
    public function getUpdates()
    {
        $base_branch = 'refs/heads/' . $this->environment->getBranchName();
        return $this->request()->request("sites/{$this->environment->site->id}/code-upstream-updates?base_branch=$base_branch")['data'];
    }

    /**
     * Determines whether there are any updates to be applied.
     *
     * @return boolean
     */
    public function hasUpdates()
    {
        return ($this->getUpdates()->behind > 0);
    }
}
