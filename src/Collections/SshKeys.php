<?php

namespace Pantheon\Terminus\Collections;

use Terminus\Exceptions\TerminusException;

class SshKeys extends UserOwnedCollection
{
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/keys';

    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\SshKey';

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
            'users/' . $this->user->id . '/keys',
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
            'users/' . $this->user->id . '/keys',
            ['method' => 'delete',]
        );
        return (array)$response['data'];
    }

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @param array $options params to pass to url request
     * @return SshKeys $this
     */
    public function fetch(array $options = [])
    {
        $results = $this->getCollectionData($options);
        foreach ($results as $uuid => $ssh_key) {
            $model_data = (object)['id' => $uuid, 'key' => $ssh_key,];
            $this->add($model_data);
        }

        return $this;
    }
}
