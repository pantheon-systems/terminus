<?php

namespace Pantheon\Terminus\DataStore;

class FileStore implements DataStoreInterface
{
    /**
     * @var string The directory to store the data files in.
     */
    protected $directory;

    public function __construct($directory)
    {
        $this->directory = $directory;
    }

    /**
     * Reads retrieves data from the store
     *
     * @param string $key A key
     * @param null $group
     * @return mixed The value fpr the given key or null.
     */
    public function get($key, $group = null)
    {
        $out = null;
        // Read the json encoded value from disk if it exists.
        $path = $this->getFileName($key, $group);
        if (file_exists($path)) {
            $out = file_get_contents($path);
            $out = json_decode($out);
        }
        return $out;
    }

    /**
     * Saves a value with the given key
     *
     * @param string $key A key
     * @param mixed $data Data to save to the store
     * @param null $group
     */
    public function set($key, $data, $group = null)
    {
        $path = $this->getFileName($key, $group);
        file_put_contents($path, json_encode($data));
    }

    /**
     * Checks if a key is in the store
     *
     * @param string $key A key
     * @param null $group
     * @return bool Whether a value exists with the given key
     */
    public function has($key, $group = null)
    {
        $path = $this->getFileName($key, $group);
        return file_exists($path);
    }

    /**
     * Remove value from the store
     *
     * @param string $key A key
     * @param null $group
     */
    public function remove($key, $group = null)
    {
        $path = $this->getFileName($key, $group);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Return a list of all keys in the store.
     *
     * @param string|null $group Limit the list to this group if specified.
     * @return array A list of keys
     */
    public function keys($group = null)
    {
        $root = $this->directory;
        if ($group) {
            $root = $root . '/' . $this->cleanKey($group);
        }
        return array_diff(scandir($root), array('..', '.'));
    }

    /**
     * Get a valid file name for the given key.
     * @param string $key The data key to be written or read
     * @return string A file path
     */
    protected function getFileName($key, $group = null)
    {
        $key = $this->cleanKey($key);
        // If there is a group put the file in a directory with that name.
        if ($group) {
            $key = $this->cleanKey($group) . '/' . $key;
        }
        return $this->directory . '/' . $key;
    }

    /**
     * Make the file path safe by whitelisting characters.
     * This is a very naive approach to hashing but in practice this doesn't matter since this is only used for a
     * few already safe keys.
     *
     * @param $key
     * @return mixed
     */
    protected function cleanKey($key)
    {
        return preg_replace('/[^a-zA-Z0-9\-\_\@\.]/', '-', $key);
    }
}
