<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class MachineToken
 * @package Pantheon\Terminus\Models
 */
class MachineToken extends TerminusModel
{

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options with which to configure this model
     */
    public function __construct($attributes, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->user = $options['collection']->getUser();
    }

    /**
     * Deletes machine token
     * @return void
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function delete()
    {
        $response = $this->request->request(
            "users/{$this->user->id}/machine_tokens/{$this->id}",
            ['method' => 'delete',]
        );
        if ($response['status_code'] !== 200) {
            throw new TerminusException('There was an problem deleting the machine token.');
        }
    }
}
