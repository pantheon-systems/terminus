<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Session\SessionAwareInterface;
use Pantheon\Terminus\Session\SessionAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

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
        $token_path = $this->getConfig()->get('tokens_dir') . "/{$this->id}";
        file_put_contents($token_path, json_encode($this->attributes));
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
