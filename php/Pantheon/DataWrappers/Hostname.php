<?php

namespace Pantheon\DataWrappers;

class Hostname extends RestRemote {
  
  protected $environment;
  
  // correct mislabled return vars from the call
  function __construct($incoming) {
    $this->uuid = $incoming->site;
    $this->name = $incoming->uuid;
    $this->environment = $incoming->environment;
  }

  function __toString() {
    return print_r($this, true);
  }
  
}