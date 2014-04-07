<?php

namespace Pantheon\DataWrappers;

class Environment extends RestRemote {

  /**
   * toString method
   *
   * @return void
   * @author stovak
   */
  function __toString() {
    return $this->getName();
  }
  /**
   * response row specifc to this data wrapper
   *
   * @param array $columns 
   * @return void
   * @author stovak
   */
  public function getTableRow(array $columns) {
    return array(
      $this->getName(),
      date('jS F Y h:i:s A (T)', $this->environment_created),
      ($this->lock->locked ? "Locked" : "Not Locked")
    );
  }


}