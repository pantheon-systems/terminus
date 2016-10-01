<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Session\Session;
use Terminus\Models\TerminusModel;

/**
 * Class SavedToken
 * @package Pantheon\Terminus\Models
 */
class SavedToken extends TerminusModel
{
    /**
     * @var Session
     */
    public $session;
    /**
     * @inheritdoc
     */
    public function __construct($attributes = null, array $options = [])
    {
        parent::__construct($attributes, $options);
        $this->session = $options['collection']->session;
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
        $this->session->setData((array)$response['data']);
        return $this->session->getUser();
    }

    /**
     * Saves this token to a file in the tokens cache dir
     */
    public function saveToDir()
    {
        $this->set('date', time());
        $config = new Config();
        $token_path = $config->get('tokens_dir') . "/{$this->id}";
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
