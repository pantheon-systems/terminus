<?php


namespace Pantheon;

class Exception extends \Exception {
  
  function  __construct($message = null, $code = 0, Exception $previous = null) {
    parent::__construct($message, $code, $previous);
    \Terminus::error($this->__toString());
  }
  
  public function __toString() {
    \Terminus::line(print_r(get_defined_vars(), true));
    echo debug_print_backtrace();
    return "EXCEPTION: ".$this->getMessage();
  }
  
}