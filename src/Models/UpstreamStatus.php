<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

/**
 * Class UpstreamStatus
 * @package Pantheon\Terminus\Models
 */
class UpstreamStatus extends TerminusModel implements EnvironmentInterface
{
    use EnvironmentTrait;

    public static $pretty_name = 'upstream status';

    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        if (isset($options['environment'])) {
            $this->setEnvironment($options['environment']);
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
        $env = $this->getEnvironment();
        $base_branch = 'refs/heads/' . $env->getBranchName();
        return $this->request()->request("sites/{$env->getSite()->id}/code-upstream-updates?base_branch=$base_branch")['data'];
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
