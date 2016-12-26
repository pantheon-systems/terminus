<?php

namespace Pantheon\Terminus\Models;

/**
 * Class UpstreamStatus
 * @package Pantheon\Terminus\Models
 */
class UpstreamStatus extends TerminusModel
{
    /**
     * @var Env
     */
    public $env;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        if (isset($options['environment'])) {
            $this->env = $options['environment'];
        }
    }

    /**
     * Returns the status of this site's upstream updates
     *
     * @return string $status 'outdated' or 'current'
     */
    public function getStatus()
    {
        if ($this->hasUpdates()) {
            $status = 'outdated';
        } else {
            $status = 'current';
        }
        return $status;
    }

    /**
     * Retrives upstream updates
     *
     * @return \stdClass
     */
    public function getUpdates()
    {
        if (!$this->env->isMultidev()) {
            $base_branch = 'master';
        } else {
            $base_branch = $this->env->id;
        }
        return $this->request()->request("sites/{$this->env->site->id}/code-upstream-updates?base_branch={$base_branch}")['data'];
    }

    /**
     * Determines whether there are any updates to be applied.
     *
     * @return boolean
     */
    public function hasUpdates()
    {
        $updates = $this->getUpdates();
        return ($updates->behind > 0);
    }
}
