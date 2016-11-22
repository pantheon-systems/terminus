<?php

namespace Pantheon\Terminus\Models;

class Lock extends TerminusModel
{
    /**
     * @var Environment
     */
    public $environment;

    /**
     * @inheritdoc
     */
    public function __construct($attributes, $options = [])
    {
        parent::__construct($attributes, $options);
        $this->environment = $options['environment'];
    }

    /**
     * Enable HTTP Basic Access authentication on the web environment
     *
     * @param array $params Elements as follow:
     *        string username
     *        string password
     * @return Workflow
     */
    public function add($params)
    {
        return $this->environment->getWorkflows()->create('lock_environment', compact('params'));
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
            'locked' => $this->isLocked() ? 'true' : 'false',
            'username' => $this->get('username'),
            'password' => $this->get('password'),
        ];
    }

    /**
     * Disable HTTP Basic Access authentication on the web environment
     *
     * @return Workflow
     */
    public function remove()
    {
        return $this->environment->getWorkflows()->create('unlock_environment');
    }
}
