<?php

namespace Terminus\Models;

class SshKey extends TerminusModel {
  /**
   * @var User
   */
  public $user;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options to set as $this->key
   * @return SshKey
   */
  public function __construct(array $attributes = [], array $options = []) {
    parent::__construct($attributes, $options);
    $this->user = $options['collection']->user;
  }

  /**
   * Delete a hostname from an environment
   *
  /**
   * Deletes a specific SSH key
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      'users/' . $this->user->id . '/keys/' . $this->id,
      ['method' => 'delete',]
    );
    return (array)$response['data'];
  }

  /**
   * Returns the comment for this SSH key
   *
   * @return string
   */
  public function getComment() {
    $key_parts = explode(' ', $this->key);
    $comment   = $key_parts[2];
    return $comment;
  }

  /**
   * Returns the hex for this SSH key
   *
   * @return string
   */
  public function getHex() {
    $hex = implode(':', str_split($this->id, 2));
    return $hex;
  }

}
