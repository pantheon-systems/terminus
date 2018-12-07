<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Friends\UserInterface;
use Pantheon\Terminus\Friends\UserTrait;

/**
 * Class SSHKey
 * @package Pantheon\Terminus\Models
 */
class SSHKey extends TerminusModel implements UserInterface
{
    use UserTrait;

    const PRETTY_NAME = 'SSH key';

    /**
     * Deletes a specific SSH key
     * @return array
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function delete()
    {
        $response = $this->request->request(
            'users/' . $this->getUser()->id . '/keys/' . $this->id,
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
        $comment = isset($key_parts[2]) ? $key_parts[2] : '';
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

    /**
     * Formats the object into an associative array for output
     *
     * @return array Associative array of data for output
     */
    public function serialize()
    {
        return array(
            'id' => $this->id,
            'hex' => $this->getHex(),
            'comment' => $this->getComment(),
        );
    }
}
