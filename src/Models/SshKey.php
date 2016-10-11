<?php

namespace Pantheon\Terminus\Models;

use Terminus\Exceptions\TerminusException;

class SshKey extends TerminusModel
{
    /**
     * @var User
     */
    public $user;

    /**
     * Object constructor
     *
     * @param object $attributes Attributes of this model
     * @param array $options Options to configure this model
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->user = $options['collection']->getUser();
    }

    /**
     * Deletes a specific SSH key
     * @return array
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function delete()
    {
        $response = $this->request->request(
            'users/' . $this->user->id . '/keys/' . $this->id,
            ['method' => 'delete',]
        );
        if ($response['status_code'] !== 200) {
            throw new TerminusException('There was an problem deleting the SSH key.');
        }
    }

    /**
     * Returns the comment for this SSH key
     *
     * @return string
     */
    public function getComment()
    {
        $key_parts = explode(' ', $this->get('key'));
        $comment = $key_parts[2];
        return $comment;
    }

    /**
     * Returns the hex for this SSH key
     *
     * @return string
     */
    public function getHex()
    {
        $hex = implode(':', str_split($this->id, 2));
        return $hex;
    }
}
