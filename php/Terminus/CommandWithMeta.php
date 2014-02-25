<?php

namespace Terminus;

/**
 * Base class for Terminus commands that deal with metadata
 *
 * @package terminus
 */
abstract class CommandWithMeta extends \Terminus_Command {

  protected $meta_type;

  /**
   * Get meta field value.
   *
   * @synopsis <id> <key> [--format=<format>]
   */
  public function get( $args, $assoc_args ) {
    list( $object_id, $meta_key ) = $args;

    $value = \get_metadata( $this->meta_type, $object_id, $meta_key, true );

    if ( '' === $value )
      die(1);

    \Terminus::print_value( $value, $assoc_args );
  }

  /**
   * Delete a meta field.
   *
   * @synopsis <id> <key>
   */
  public function delete( $args, $assoc_args ) {
    list( $object_id, $meta_key ) = $args;

    $success = \delete_metadata( $this->meta_type, $object_id, $meta_key );

    if ( $success ) {
      \Terminus::success( "Deleted custom field." );
    } else {
      \Terminus::error( "Failed to delete custom field." );
    }
  }

  /**
   * Add a meta field.
   *
   * ## OPTIONS
   *
   * <id>
   * : The ID of the object.
   *
   * <key>
   * : The name of the meta field to create.
   *
   * [<value>]
   * : The value of the meta field. If ommited, the value is read from STDIN.
   *
   * [--format=<format>]
   * : The serialization format for the value. Default is plaintext.
   */
  public function add( $args, $assoc_args ) {
    list( $object_id, $meta_key ) = $args;

    $meta_value = \Terminus::get_value_from_arg_or_stdin( $args, 2 );
    $meta_value = \Terminus::read_value( $meta_value, $assoc_args );

    $success = \add_metadata( $this->meta_type, $object_id, $meta_key, $meta_value );

    if ( $success ) {
      \Terminus::success( "Added custom field." );
    } else {
      \Terminus::error( "Failed to add custom field." );
    }
  }

  /**
   * Update a meta field.
   *
   * ## OPTIONS
   *
   * <id>
   * : The ID of the object.
   *
   * <key>
   * : The name of the meta field to update.
   *
   * [<value>]
   * : The new value. If ommited, the value is read from STDIN.
   *
   * [--format=<format>]
   * : The serialization format for the value. Default is plaintext.
   *
   * @alias set
   */
  public function update( $args, $assoc_args ) {
    list( $object_id, $meta_key ) = $args;

    $meta_value = \Terminus::get_value_from_arg_or_stdin( $args, 2 );
    $meta_value = \Terminus::read_value( $meta_value, $assoc_args );

    $success = \update_metadata( $this->meta_type, $object_id, $meta_key, $meta_value );

    if ( $success ) {
      \Terminus::success( "Updated custom field." );
    } else {
      \Terminus::error( "Failed to update custom field." );
    }
  }
}

