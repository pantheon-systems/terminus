<?php

namespace Pantheon\Terminus\DataStore;

class MemoryStore implements DataStoreInterface
{
    /**
     * @var array The in-memory data.
     */
    protected $data;

    /**
     * Reads retrieves data from the store
     *
     * @param string $key A key
     * @return mixed The value fpr the given key or null.
     */
    public function get($key)
    {
        return $this->has($key) ? $this->data[$key] : null;
    }

    /**
     * Saves a value with the given key
     *
     * @param string $key A key
     * @param mixed $data Data to save to the store
     */
    public function set($key, $data)
    {
        $this->data[$key] = $data;
    }

    /**
     * Checks if a key is in the store
     *
     * @param string $key A key
     * @return bool Whether a value exists with the given key
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove value from the store
     *
     * @param string $key A key
     */
    public function remove($key)
    {
        unset($this->data[$key]);
    }
}
