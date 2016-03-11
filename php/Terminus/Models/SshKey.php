<?php

namespace Terminus\Models;

class SshKey extends TerminusModel {

  /**
   * Returns the hex for this key
   *
   * @return string
   */
  public function getHex() {
    $hex = implode(':', str_split($this->get('id'), 2));
    return $hex;
  }

}
