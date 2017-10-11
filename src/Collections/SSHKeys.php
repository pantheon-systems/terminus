<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SSHKey;

/**
 * Class SSHKeys
 * @package Pantheon\Terminus\Collections
 */
class SSHKeys extends UserOwnedCollection
{
    public static $pretty_name = 'SSH keys';
    /**
     * @var string
     */
    protected $collected_class = SSHKey::class;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/keys';

    /**
     * Adds an SSH key to the user's Pantheon account
     *
     * @param string $key_file Full path of the SSH key to add
     * @return array
     * @throws TerminusException
     */
    public function addKey($key_file)
    {
        if (!file_exists($key_file)) {
            throw new TerminusException(
                'The file {file} cannot be accessed by Terminus.',
                ['file' => $key_file,],
                1
            );
        }
        $response = $this->request->request(
            'users/' . $this->getUser()->id . '/keys',
            [
                'form_params' => rtrim(file_get_contents($key_file)),
                'method' => 'post',
            ]
        );
        return (array)$response['data'];
    }

    /**
     * Deletes all SSH keys from account
     *
     * @return array
     */
    public function deleteAll()
    {
        $response = $this->request->request(
            'users/' . $this->getUser()->id . '/keys',
            ['method' => 'delete',]
        );
        return (array)$response['data'];
    }

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @return SSHKeys $this
     */
    public function fetch()
    {
        if (!is_null($data = $this->getData())) {
            foreach ($data as $uuid => $ssh_key) {
                $model_data = (object)['id' => $uuid, 'key' => $ssh_key,];
                $this->add($model_data);
            }
        }

        return $this;
    }
}
