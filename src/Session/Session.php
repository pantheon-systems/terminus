<?php

namespace Pantheon\Terminus\Session;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\User;

/**
 * Class Session
 * @package Pantheon\Terminus\Session
 */
class Session implements ContainerAwareInterface, ConfigAwareInterface, DataStoreAwareInterface
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
     * Instantiates object, sets session data, instantiates a SavedTokens instance
     *
     * @param DataStoreInterface $data_store An object to persist the session data.
     */
    public function __construct(DataStoreInterface $data_store)
    {
        $this->setDataStore($data_store);
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
     * Returns given data property or default if DNE.
     *
     * @param string $key Name of property to return
     * @return mixed
     * @throws TerminusException If the given key is not located
     */
    public function get($key)
    {
        if (isset($this->data->$key)) {
            return $this->data->$key;
        }
        return null;
    }

    /**
     * Returns a user with the current session user id
     * @return \Pantheon\Terminus\Models\User [user] $session user
     */
    public function getUser()
    {
        return $this->getContainer()->get(User::class, [(object)['id' => $this->get('user_id'),],]);
    }

    /**
     * Responds with the status of this session (i.e. whether the client is logged in)
     *
     * @return boolean
     */
    public function isActive()
    {
        return (
            isset($this->data->session)
            && ($this->data->expires_at >= time() || (boolean)$this->config->get('test_mode'))
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
     * @return \Pantheon\Terminus\Collections\SavedTokens
     */
    public function getTokens()
    {
        if (empty($this->tokens)) {
            $this->tokens = $this->getContainer()->get(SavedTokens::class, [['session' => $this,],]);
        }
        return $this->tokens;
    }

    /**
     * @param DataStoreInterface $data_store
     */
    public function setDataStore(DataStoreInterface $data_store)
    {
        $this->data_store = $data_store;
        $this->data = (object)$data_store->get('session');
    }
}
