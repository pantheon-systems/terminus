<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;

class SshKeys extends NewCollection {
  /**
   * @var User
   */
  public $user;
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\SshKey';

  /**
   * Instantiates the collection
   *
   * @param array $options To be set
   * @return SshKeys
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->user = $options['user'];
    $this->url  = "sites/{$this->user->id}/keys";
  }

  /**
   * Adds an SSH key to the user's Pantheon account
   *
   * @param string $key_file Full path of the SSH key to add
   * @return array
   * @throws TerminusException
   */
  public function create($key_file) {
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
        'form_params' => file_get_contents($key_file),
        'method'      => 'delete',
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

}
