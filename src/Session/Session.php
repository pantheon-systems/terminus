<?php

namespace Pantheon\Terminus\Session;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Collections\SavedTokens;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Terminus\Caches\FileCache;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\User;

class Session implements ContainerAwareInterface, ConfigAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;

    /**
     * @var SavedTokens
     */
    public $tokens;
    /**
     * @var FileCache
     */
    protected $cache;
    /**
     * @var object
     */
    protected $data;

    /**
     * Instantiates object, sets session data, instantiates a SavedTokens instance
     *
     * @param FileCache $file_cache A file cache object
     */
    public function __construct($file_cache)
    {
        $this->cache = $file_cache;
        $this->data = (object)$this->cache->getData('session');
    }

    /**
     * Removes the session from the cache
     */
    public function destroy()
    {
        $this->cache->remove('session');
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
        $user_id = $this->get('user_id');
        $user = $this->getContainer()->get(User::class, [(object)array('id' => $user_id)]);
        return $user;
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
        $this->cache->putData('session', $data);
        $this->data = (object)$data;
    }

    /**
     * @return \Pantheon\Terminus\Collections\SavedTokens
     */
    public function getTokens()
    {
        if (empty($this->tokens)) {
            $this->tokens = $this->getContainer()->get(SavedTokens::class, [['session' => $this,]]);
        }
        return $this->tokens;
    }
}
