<?php

namespace Pantheon\DataWrappers;

class Organization extends RestRemote {

  protected $instrument;
  protected $maxdevsites;
  protected $name;
  protected $admin;

  public function getTableRow(array $columns) {
    return array($this->uuid, $this->name);
  }

}