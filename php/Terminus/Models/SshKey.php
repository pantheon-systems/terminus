<?php

namespace Terminus\Models;

class SshKey extends TerminusModel {

  /**
   * Deletes a specific SSH key
   *
   * @return array
   */
  public function delete() {
    $response = $this->request->request(
      'users/' . $this->user->id . '/keys/' . $this->get('id'),
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
    $key_parts = explode(' ', $this->get('key'));
    $comment   = $key_parts[2];
    return $comment;
  }

  /**
   * Returns the hex for this SSH key
   *
   * @return string
   */
  public function getHex() {
    $hex = implode(':', str_split($this->get('id'), 2));
    return $hex;
  }

}
