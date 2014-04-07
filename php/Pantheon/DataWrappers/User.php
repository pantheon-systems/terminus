<?php

namespace Pantheon\DataWrappers;

class User extends RestRemote {


  public function getName() {
    return print_r($this, TRUE);
  }

  function __toString() {
    return $this->getName();
  }

  static function fromUUID($uuid) {
    return \Terminus\DataWrappers\Request::getResponse("user", $uuid, "profile", "GET", array(), "UserList")->first();
  }

}