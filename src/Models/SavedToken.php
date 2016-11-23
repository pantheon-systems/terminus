<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SavedToken
 * @package Pantheon\Terminus\Models
 */
class SavedToken extends TerminusModel implements SessionAwareInterface, ConfigAwareInterface
{
    use SessionAwareTrait;
    use ConfigAwareTrait;

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
        $this->set('date', time());
        file_put_contents($this->getPath(), json_encode($this->attributes));
    }

    /**
     * Delete the token.
     */
    public function delete()
    {
        unlink($this->getPath());
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

    /**
     * Get the path to save the token to.
     *
     * @return string The file path for the token file.
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getPath()
    {
        $path = $this->getConfig()->get('tokens_dir');
        $id = $this->id;
        // Reality check to prevent stomping on the local filesystem if there is something wrong with the config.
        if (!$path) {
            throw new TerminusException('Could not save the machine token because the token path is mis-configured.');
        }
        if (!$this->id) {
            throw new TerminusException('Could not save the machine token because it is missing an ID');
        }
        return "$path/$id";
    }
}
