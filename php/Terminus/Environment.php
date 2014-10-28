<?php
namespace Terminus;

abstract class Environment {
  public $name = 'dev';
  public $site_id = false;

  public function __construct($site_id) {
    $this->site_id = $site_id;
  }

  public function wipe() {
    return \Terminus_Command::request('sites', $this->site_id, "environments/{$this->name}/wipe", 'POST');
  }

}
