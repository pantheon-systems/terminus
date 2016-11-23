<?php

namespace Pantheon\Terminus\DataStore;

use Terminus\Exceptions\TerminusException;

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
     * @return mixed The value fpr the given key or null.
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function get($key)
    {
        $out = null;
        // Read the json encoded value from disk if it exists.
        $path = $this->getFileName($key);
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
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function set($key, $data)
    {
        $path = $this->getFileName($key);
        file_put_contents($path, json_encode($data));
    }

    /**
     * Checks if a key is in the store
     *
     * @param string $key A key
     * @return bool Whether a value exists with the given key
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function has($key)
    {
        $path = $this->getFileName($key);
        return file_exists($path);
    }

    /**
     * Remove value from the store
     *
     * @param string $key A key
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function remove($key)
    {
        $path = $this->getFileName($key);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Return a list of all keys in the store.
     * @return array A list of keys
     */
    public function keys()
    {
        $root = $this->directory;
        return array_diff(scandir($root), array('..', '.'));
    }

    /**
     * Get a valid file name for the given key.
     * @param string $key The data key to be written or read
     * @return string A file path
     * @throws TerminusException
     */
    protected function getFileName($key)
    {
        $key = $this->cleanKey($key);

        // Reality check to prevent stomping on the local filesystem if there is something wrong with the config.
        if (!$this->directory) {
            throw new TerminusException('Could not save data to a file because the path setting is mis-configured.');
        }
        if (!$key) {
            throw new TerminusException('Could not save data to a file because it is missing an ID');
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
