<?php

namespace Pantheon\Terminus\Session;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Terminus\Caches\FileCache;
use Terminus\Models\User;

class Session implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var FileCache
     */
    protected $cache;

    /**
     * @var object
     */
    protected $data;

    /**
     * Instantiates object, sets session data
     */
    public function __construct($fileCache)
    {
        $this->cache = $fileCache;
        $session = $this->cache->getData('session');
        $this->data = $session;
        if (empty($session)) {
            $this->data = new \stdClass();
        }
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
        if (isset($this->data) && isset($this->data->$key)) {
            return $this->data->$key;
        }
        return $default;
    }

    /**
     * Sets a keyed value to be part of the data property object
     *
     * TODO: This is never used and arguably shouldn't be (since it doesn't save
     *       data to the persistent session store)
     *       This should be removed in favor of setData which is designed to
     *       instantiate the entire session or changed to add persistent data.
     *
     * @param string $key Name of data property
     * @param mixed $value Value of property to set
     * @return Session
     */
    public function set($key, $value = null)
    {
        $this->data->$key = $value;
        return $this;
    }

    /**
     * Saves session data to cache
     *
     * TODO: The difference between set and setData is confusing. This should be
     *       refactored when Auth() is.
     *
     * @param array $data Session data to save
     * @return bool
     */
    public function setData($data)
    {
        if (empty($data)) {
            return false;
        }
        $this->cache->putData('session', $data);

        foreach ($data as $k => $v) {
            $this->set($k, $v);
        }
        return true;
    }

    /**
     * Returns a user with the current session user id
     * @return \Terminus\Models\User [user] $session user
     */
    public function getUser()
    {
        $user_uuid = $this->get('user_uuid');
        $user = $this->getContainer()->get(User::class, [(object)array('id' => $user_uuid)]);
        return $user;
    }
}
