<?php

namespace Pantheon\Terminus\Session;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Config\ConfigAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class Session
 *
 * @package Pantheon\Terminus\Session
 */
class Session implements
    ContainerAwareInterface,
    ConfigAwareInterface,
    DataStoreAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use DataStoreAwareTrait;

    /**
     * @var SavedTokens
     */
    public $tokens;

    /**
     * @var object
     */
    protected $data;

    /**
     * Instantiates object, sets session data, instantiates a SavedTokens
     * instance
     *
     * @param DataStoreInterface $data_store An object to persist the session
     *     data.
     */
    public function __construct(DataStoreInterface $data_store)
    {
        $this->setDataStore($data_store);
    }

    /**
     * @param DataStoreInterface $data_store
     */
    public function setDataStore(DataStoreInterface $data_store)
    {
        $this->data_store = $data_store;
        $this->data = (object)$data_store->get('session');
    }

    /**
     * Removes the session from the cache
     */
    public function destroy()
    {
        $this->getDataStore()->remove('session');
        $this->data = (object)[];
    }

    /**
     * Returns a user with the current session user id
     *
     * @return \Pantheon\Terminus\Models\User [user] $session user
     */
    public function getUser(): User
    {
        $user_id = $this->get('user_id');
        if (empty($user_id)) {
            throw new TerminusException(
                "No user ID. Please login via terminus auth:login"
            );
        }
        $nickname = \uniqid(__METHOD__ . "-");
        $this->getContainer()
            ->add($nickname, User::class)
            ->addArgument((object)['id' => $user_id]);
        $user = $this->getContainer()->get($nickname);
        if (!$user instanceof User) {
            throw new TerminusException(
                "No User ID. Please ling via terminus auth:login"
            );
        }
        return $user;
    }

    /**
     * Returns given data property or default if DNE.
     *
     * @param string $key Name of property to return
     *
     * @return mixed
     * @throws TerminusException If the given key is not located
     */
    public function get($key)
    {
        if (isset($this->data->{$key})) {
            return $this->data->{$key};
        }
        return null;
    }

    /**
     * Responds with the status of this session (i.e. whether the client is
     * logged in)
     *
     * @return boolean
     */
    public function isActive()
    {
        // Consider a session expired if it is already about to expire (less than 1 minute).
        $hasToken = isset($this->data->session) || isset($this->data->access_token);
        return (
            $hasToken
            && ($this->data->expires_at >= (time() + 60) || (bool)$this->config->get(
                'test_mode'
            ))
        );
    }

    /**
     * Saves session data to cache
     *
     * @param array $data Session data to save
     */
    public function setData($data)
    {
        $this->getDataStore()->set('session', $data);
        $this->data = (object)$data;
    }

    /**
     * Selects the SavedToken to (re-)authenticate with: a single saved token, or
     * the token matching the configured 'user' email.
     *
     * @return SavedToken
     * @throws TerminusException If there is no unambiguous token to use.
     */
    public function getAuthToken(): SavedToken
    {
        $tokens_obj = $this->getTokens();
        if (count($tokens = $tokens_obj->all()) == 1) {
            return array_shift($tokens);
        }
        if (!empty($email = $this->getConfig()->get('user'))) {
            return $tokens_obj->get($email);
        }
        throw new TerminusException(
            'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
        );
    }

    /**
     * @return \Pantheon\Terminus\Collections\SavedTokens
     */
    public function getTokens()
    {
        if (empty($this->tokens)) {
            $nickname = \uniqid(__METHOD__ . "-");
            $this->getContainer()->add($nickname, SavedTokens::class)
                ->addMethodCall('setDataStore', [$this->data_store]);
            $this->tokens = $this->getContainer()->get($nickname);
        }
        return $this->tokens;
    }
}
