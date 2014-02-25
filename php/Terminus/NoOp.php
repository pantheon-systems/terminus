<?php

namespace Terminus;

final class NoOp {

  function __set( $key, $value ) {
    // do nothing
  }

  function __call( $method, $args ) {
    // do nothing
  }
}

