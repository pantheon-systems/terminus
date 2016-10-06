<?php

namespace Pantheon\Terminus\DataStore;

/**
 * Interface DataStoreInterface
 */
interface DataStoreInterface
{
    /**
     * Reads retrieves data from the store
     *
     * @param string $key A key
     * @param string|null $group Retrieve the data from this named group if specified.
     * @return mixed The value fpr the given key or null.
     */
    public function get($key, $group = null);

    /**
     * Saves a value with the given key
     *
     * @param string $key A key
     * @param mixed $data Data to save to the store
     * @param string|null $group Put the data into this named group.
     * @return
     */
    public function set($key, $data, $group = null);

    /**
     * Checks if a key is in the store
     *
     * @param string $key A key
     * @param string|null $group Check in this named group if specified.
     * @return bool Whether a value exists with the given key
     */
    public function has($key, $group = null);

    /**
     * Remove value from the store
     *
     * @param string $key A key
     * @param null $group Remove from this named group.
     * @return
     */
    public function remove($key, $group = null);

    /**
     * Return a list of all keys in the store.
     *
     * @param string|null $group Limit the list to this group if specified.
     * @return array A list of keys
     */
    public function keys($group = null);

}
