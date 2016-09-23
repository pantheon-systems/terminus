<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusException;

class SshKeys extends TerminusCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\SshKey';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
  public function __construct($options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url = "users/{$this->user->id}/keys";
  }

  /**
   * Adds an SSH key to the user's Pantheon account
   *
   * @param string $key_file Full path of the SSH key to add
   * @return array
   * @throws TerminusException
   */
  public function addKey($key_file) {
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
        // Trim the newline from the end of the file or creates invalid JSON.
        'form_params' => rtrim(file_get_contents($key_file)),
        'method'      => 'post',
      ]
    );
    return (array)$response['data'];
  }

  /**
   * Deletes all SSH keys from account
   *
   * @return array
   */
  public function deleteAll() {
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
  public function fetch(array $options = []) {
    $results = $this->getCollectionData($options);
    foreach ($results['data'] as $uuid => $ssh_key) {
      $model_data = (object)['id' => $uuid, 'key' => $ssh_key,];
      $this->add($model_data);
    }

    return $this;
  }

}
