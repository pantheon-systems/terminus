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

    const PRETTY_NAME = 'upstream status';

    /**
     * @var object|null
     */
    protected $updates = null;

     /**
      * Stores composer dependency updates.
      *
      * @var object|null
      */
    protected $composerUpdates = null;

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
        return $this->hasUpdates() || $this->hasComposerUpdates() ? 'outdated' : 'current';
    }

    /**
     * Retrives upstream updates
     *
     * @return object
     */
    public function getUpdates()
    {
        if ($this->updates === null) {
            $env = $this->getEnvironment();
            $base_branch = 'refs/heads/' . $env->getBranchName();
            $this->updates = $this->request()->request(
                "sites/{$env->getSite()->id}/code-upstream-updates?base_branch=$base_branch"
            )['data'];
        }
        return $this->updates;
    }

    /**
     * Retrives composer dependecy updates
     *
     * @return object
     */
    public function getComposerUpdates()
    {
        if ($this->composerUpdates === null) {
            $env = $this->getEnvironment();
            $this->composerUpdates = $this->request()->request(
                "sites/{$env->getSite()->id}/environments/{$env->id}/build/updates"
            )['data'];
        }
        return $this->composerUpdates;
    }

    /**
     * @return bool
     */
    public function hasCode()
    {
        return $this->getUpdates()->has_code;
    }

    /**
     * @return bool
     */
    public function hasComposerUpdates()
    {
        $composerUpdates = $this->getComposerUpdates();
        return !empty($composerUpdates->added_dependencies) ||
            !empty($composerUpdates->updated_dependencies) ||
            !empty($composerUpdates->removed_dependencies);
    }

    /**
     * Determines whether there are any updates to be applied.
     *
     * @return bool
     */
    public function hasUpdates()
    {
        if (!$this->hasCode()) {
            return false;
        }

        $updates = $this->getUpdates();
        $env = $this->getEnvironment();
        if ($env->isDevelopment()) {
            return ($updates->behind > 0);
        }

        return !($updates->{$env->id}->is_up_to_date_with_upstream
            && $updates->{$env->getParentEnvironment()->id}->is_up_to_date_with_upstream);
    }
}
