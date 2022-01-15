<?php

namespace Pantheon\Terminus\DataStore;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class FileStore
 *
 * @package Pantheon\Terminus\DataStore
 */
class FileStore extends \DirectoryIterator implements DataStoreInterface
{

    /**
     * Creates FileStore instance.
     *
     * Creates directory if it doesn't exist before invoking DirectoryIterator constructor.
     */
    public function __construct(string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        parent::__construct($directory);
    }

    /**
     * Reads retrieves data from the store
     *
     * @param string $key A key
     *
     * @return mixed The value fpr the given key or null.
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function get($key)
    {
        $tokenFilename = $this->getRealPath() . DIRECTORY_SEPARATOR . $this->cleanKey($key);
        if (file_exists($tokenFilename) && is_file($tokenFilename)) {
            try {
                $toReturn = json_decode(
                    file_get_contents($tokenFilename),
                    false,
                    512,
                    JSON_THROW_ON_ERROR
                );
                if ($toReturn instanceof \stdClass) {
                    $toReturn->id = $key;
                }
                return $toReturn;
            } catch (\Exception $e) {
                // TODO: handle not found error
            }
        }
        return null;
    }


    /**
     * Make the file path safe by whitelisting characters.
     * This is a very naive approach to hashing but in practice this doesn't matter since this is only used for a
     * few already safe keys.
     *
     * @param $key
     *
     * @return mixed
     */
    protected function cleanKey($key)
    {
        return preg_replace('/[^a-zA-Z0-9\-\_\@\.]/', '-', $key);
    }

    /**
     * Saves a value with the given key
     *
     * @param string $key A key
     * @param mixed $data Data to save to the store
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function set($key, $data)
    {
        $path = $this->getRealPath() . DIRECTORY_SEPARATOR . $this->cleanKey($key);
        // Prevent categories group+other from reading, writing or executing
        // any files written to the FileStore, for security/privacy.
        // e.g. tokens are cached and could be read by other user accounts
        // on the machine.
        $old = umask(077);
        file_put_contents($path, json_encode($data));
        umask($old);
    }

    /**
     * Checks if a key is in the store
     *
     * @param string $key A key
     *
     * @return bool Whether a value exists with the given key
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function has($key)
    {
        return file_exists($this->getRealPath() . DIRECTORY_SEPARATOR . $this->cleanKey($key));
    }

    /**
     * Remove all values from the store
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function removeAll()
    {
        foreach ($this->keys() as $key) {
            $this->remove($key);
        }
    }

    /**
     * Return a list of all keys in the store.
     *
     * @return array A list of keys
     */
    public function keys()
    {
	print ">>> get keys from " . $this->getRealPath() . "\n";
        $toReturn = array_diff(scandir($this->getRealPath()), ['..', '.']);
        return array_values($toReturn);
    }

    /**
     * Remove value from the store
     *
     * @param string $key A key
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function remove($key)
    {
        $path = $this->getRealPath() . "/" . $key;
        if (file_exists($path)) {
            unlink($path);
        }
    }

    /**
     * Check that the directory is writable and create it if we can.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function ensure()
    {
        $this->rewind();
        // Reality check to prevent stomping on the local filesystem if there is something wrong with the config.
        // Valid() checks to see if the directory item is valid, but after a rewind, it's checking "."
        // which is essentially checking the directory itself.
        if (!$this->valid()) {
            throw new TerminusException('Could not save data to a file because the path setting is mis-configured.');
        }
        if (!$this->isDir()) {
            throw new TerminusException('Could not save data to a file because the path setting is mis-configured.');
        }
        if (!$this->isWritable()) {
            throw new TerminusException('The filesystem directory exists but is not writable');
        }
    }
}
