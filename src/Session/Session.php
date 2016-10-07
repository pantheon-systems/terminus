<?php

namespace Pantheon\Terminus\Session;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Terminus\Caches\FileCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\User;
use Terminus\Request;

class Session implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @var FileCache
     */
    protected $cache;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Instantiates object, sets session data
     * @param DataStoreInterface $store The data store to save the session to
     */
    public function __construct(DataStoreInterface $store)
    {
        $this->cache = $store;
        $session = $this->cache->get('session');
        $this->data = (array)$session;
        if (empty($session)) {
            $this->data = [];
        }

        $this->request = new Request();
    }

    /**
     * Removes the session from the cache
     *
     * @return void
     */
    public function destroy()
    {
        $this->cache->remove('session');
    }

    /**
     * Returns given data property or default if DNE.
     *
     * @param string $key Name of property to return
     * @param mixed $default Default return value in case property DNE
     * @return mixed
     */
    public function get($key, $default = false)
    {
        if (isset($this->data) && isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     * Sets a keyed value to be part of the data property object
     *
     * @param string $key Name of data property
     * @param mixed $value Value of property to set
     * @return Session
     */
    public function set($key, $value = null)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Sets a keyed value to be part of the data property object
     *
     * @param string $key Name of data property
     * @param mixed $value Value of property to set
     * @return Session
     */
    public function write()
    {
        $this->cache->set('session', $this->data);
    }

    /**
     * Saves session data to cache
     *
     * @param array $data Session data to save
     * @return bool
     */
    public function setData($data)
    {
        if (empty($data)) {
            return false;
        }

        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }

        $this->write();
        return true;
    }

    /**
     * Returns a user with the current session user id
     * @return \Terminus\Models\User [user] $session user
     */
    public function getUser()
    {
        if ($this->loggedIn()) {
            $user_id = $this->get('user_id');
            // TODO: Remove this direct instantiation to make this more testable
            $user = new User((object)array('id' => $user_id));
            return $user;
        }
    }

    /**
     * Authorize the current user prior to running a command.  The
     * Annotated Commands hook manager will call this function during
     * the pre-validate phase of any command that has an 'authorize'
     * annotation.
     *
     */
    public function ensureLogin()
    {
        if (!$this->loggedIn()) {
            $tokens = $this->getAllSavedTokenEmails();
            if (count($tokens) === 1) {
                $email = array_shift($tokens);
                $this->logInViaSavedEmailMachineToken($email);
            } elseif (!is_null($this->getConfigValue('user')) && $email = $this->getConfigValue('user')) {
                $this->logInViaSavedEmailMachineToken($email);
            } else {
                throw new TerminusException(
                    'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
                );
            }
        }
    }

    /**
     * Checks to see if the current user is logged in
     *
     * @return bool True if the user is logged in
     */
    public function loggedIn()
    {
        $is_logged_in = (
            $this->get('user_id')
            && (
                $this->getConfig()->get('test_mode')
                || ($this->get('session_expire_time') >= time())
            )
        );
        return $is_logged_in;
    }

    /**
     * Execute the login based on a machine token
     *
     * @param string $email The email address to look up the token by
     * @return bool True if login succeeded
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function logInViaSavedEmailMachineToken($email)
    {
        $saved_token = (array)$this->cache->get($email, 'tokens');
        if ($saved_token && $saved_token['token']) {
            $token = $saved_token['token'];
        } else {
            throw new TerminusException(
                'There are no saved tokens for {email}.',
                compact('email'),
                1
            );
        }

        return $this->logInViaMachineToken($token);
    }

    /**
     * Execute the login based on a machine token
     *
     * @param string $token The machine token to log in with.
     * @return bool True if login succeeded
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function logInViaMachineToken($token)
    {
        $options = [
            'form_params' => [
                'machine_token' => $token,
                'client'        => 'terminus',
            ],
            'method' => 'post',
        ];

        try {
            $response = $this->request->request(
                'authorize/machine-token',
                $options
            );
        } catch (\Exception $e) {
            throw new TerminusException(
                'The provided machine token is not valid.',
                [],
                1
            );
        }

        $this->setData((array)$response['data']);
        $user = $this->getUser();
        $user->fetch();
        $user_data = $user->serialize();
        $this->cache->set(
            $user_data['email'],
            ['email' => $user_data['email'], 'token' => $token,],
            'tokens'
        );
        return true;
    }
    
    /**
     * Logs the current user out.
     *
     * @return void
     */
    public function logOut()
    {
        $this->destroy();
    }

    /**
     * Gets all email addresses for which there are saved machine tokens
     *
     * @return string[]
     */
    public function getAllSavedTokenEmails()
    {
        $emails = $this->cache->keys('tokens');
        return $emails;
    }

    /**
     * Generates the URL string for where to create a machine token
     *
     * @return string
     */
    public function getMachineTokenCreationUrl()
    {
        $url = vsprintf(
            '%s://%s/machine-token/create/%s',
            [$this->getConfig()->get('dashboard_protocol'), $this->getConfig()->get('dashboard_host'), gethostname(),]
        );
        return $url;
    }
}
