<?php

// Utility functions used by Behat steps

function assertEquals( $expected, $actual ) {
  if ( $expected != $actual ) {
    throw new Exception( "Actual value: " . var_export( $actual, true ) );
  }
}

function assertNumeric( $actual ) {
  if ( !is_numeric( $actual ) ) {
    throw new Exception( "Actual value: " . var_export( $actual, true ) );
  }
}

function assertNotNumeric( $actual ) {
  if ( is_numeric( $actual ) ) {
    throw new Exception( "Actual value: " . var_export( $actual, true ) );
  }
}

function checkString( $output, $expected, $action, $message = false ) {
  switch ( $action ) {

  case 'be':
    $r = $expected === rtrim( $output, "\n" );
    break;

  case 'contain':
    $r = false !== strpos( $output, $expected );
    break;

  case 'not contain':
    $r = false === strpos( $output, $expected );
    break;

  default:
    throw new Behat\Behat\Exception\PendingException();
  }

  if ( !$r ) {
    if ( false === $message )
      $message = $output;
    throw new Exception( $message );
  }
}

function compareContents( $expected, $actual ) {
  if ( gettype( $expected ) != gettype( $actual ) ) {
    return false;
  }

  if ( is_object( $expected ) ) {
    foreach ( get_object_vars( $expected ) as $name => $value ) {
      if ( ! compareContents( $value, $actual->$name ) )
        return false;
    }
  } else if ( is_array( $expected ) ) {
    foreach ( $expected as $key => $value ) {
      if ( ! compareContents( $value, $actual[$key] ) )
        return false;
    }
  } else {
    return $expected === $actual;
  }

  return true;
}
