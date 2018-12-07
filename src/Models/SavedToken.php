<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SavedToken
 * @package Pantheon\Terminus\Models
 */
class SavedToken extends TerminusModel implements SessionAwareInterface, DataStoreAwareInterface
{
    use SessionAwareTrait;
    use DataStoreAwareTrait;

    const PRETTY_NAME = 'saved token';

    /**
     * Delete the token.
     */
    public function delete()
    {
        $this->getDataStore()->remove($this->id);
    }

    /**
     * @return string[]
     */
    public function getReferences()
    {
        return [$this->id, $this->get('token'),];
    }

    /**
     * Starts a session with this saved token
     *
     * @return User An object representing the now-logged-in user
     */
    public function logIn()
    {
        $options = [
            'form_params' => ['machine_token' => $this->get('token'), 'client' => 'terminus',],
            'method' => 'post',
        ];
        $response = $this->request->request('authorize/machine-token', $options);
        $this->session()->setData((array)$response['data']);
        return $this->session->getUser();
    }

    /**
     * Saves this token to a file in the tokens cache dir
     */
    public function saveToDir()
    {
        if (!$this->id) {
            throw new TerminusException('Could not save the machine token because it is missing an ID');
        }

        $this->set('date', time());
        $this->getDataStore()->set($this->id, $this->attributes);
    }

    /**
     * @inheritdoc
     */
    protected function parseAttributes($data)
    {
        if (property_exists($data, 'email')) {
            $data->id = $data->email;
        }
        return $data;
    }
}
