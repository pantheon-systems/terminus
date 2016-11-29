<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SavedToken
 * @package Pantheon\Terminus\Models
 */
class SavedToken extends TerminusModel implements SessionAwareInterface, ConfigAwareInterface, DataStoreAwareInterface
{
    use SessionAwareTrait;
    use ConfigAwareTrait;
    use DataStoreAwareTrait;

    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
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
     * Delete the token.
     */
    public function delete()
    {
        $this->getDataStore()->remove($this->id);
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
