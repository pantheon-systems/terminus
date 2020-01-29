<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;

/**
 * Class Lock
 * @package Pantheon\Terminus\Models
 */
class Lock extends TerminusModel implements EnvironmentInterface
{
    use EnvironmentTrait;

    const PRETTY_NAME = 'lock';

    /**
     * @inheritdoc
     */
    public function __construct($attributes, $options = [])
    {
        parent::__construct($attributes, $options);
        $this->setEnvironment($options['environment']);
    }

    /**
     * Enable HTTP Basic Access authentication on the web environment
     *
     * @param array $params Elements as follow:
     *        string username
     *        string password
     * @return Workflow
     */
    public function enable($params)
    {
        return $this->getEnvironment()->getWorkflows()->create('lock_environment', compact('params'));
    }

    /**
     * Returns whether the associated environment is locked
     *
     * @return bool
     */
    public function isLocked()
    {
        return (boolean)$this->get('locked');
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return [
            'locked' => $this->isLocked(),
            'username' => $this->get('username'),
            'password' => $this->get('password'),
        ];
    }

    /**
     * Disable HTTP Basic Access authentication on the web environment
     *
     * @return Workflow
     */
    public function disable()
    {
        return $this->getEnvironment()->getWorkflows()->create('unlock_environment');
    }
}
