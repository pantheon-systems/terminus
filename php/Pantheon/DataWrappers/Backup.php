<?php

namespace Pantheon\DataWrappers;

class Backup extends RestRemote {


  public function getTableRow(array $columns) {
    return array(
      $this->uuid,
      @array_pop(explode("_", $this->uuid)),
      date('jS F Y h:i:s A (T)', $this->timestamp),
      $this->folder,
      number_format((($this->size / 1024) / 1024), 1) . "MB"
    );
  }

  public function __toString() {
    return $this->getURL();
  }

  public function getURL() {
    return $this->data->url;
  }

}