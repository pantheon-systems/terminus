<?php

namespace Terminus\Dispatcher;

/**
 * Creates CompositeCommand or Subcommand instances.
 */
class CommandFactory {

  /**
   * undocumented class
   *
   * @package default
   * @author stovak
   */
  public static function create( $name, $class, $parent ) {
    return self::create_composite_command( $parent, $name, new \ReflectionClass( $class ));
  }

  /**
   * Create basic command with no subcommands
   *
   * @package default
   * @author stovak
   */

  private static function create_subcommand( $parent, $name, $class_name, $method ) {
    $docparser = new \Terminus\DocParser( $method->getDocComment() );

    if ( !$name ) {
      $name = $docparser->get_tag( 'subcommand' );
    }

    if ( !$name ) {
      $name = $method->name;
    }

    $method_name = $method->name;

    $when_invoked = function ( $args, $assoc_args ) use ( $class_name, $method_name ) {
      $reflection = new \ReflectionClass($class_name);
      $instance = $reflection->newInstance($args, $assoc_args);
      call_user_func( array( $instance, $method_name ), $args, $assoc_args );
    };

    return new Subcommand( $parent, $name, $docparser, $when_invoked );
  }

  /**
   * Create a command with subcommands
   *
   * @param string $parent 
   * @param string $name 
   * @param string $reflection 
   * @return void
   * @author stovak
   */
  private static function create_composite_command( $parent, $name, $reflection ) {
    $docparser = new \Terminus\DocParser( $reflection->getDocComment() );
    $container = new CompositeCommand( $parent, $name, $docparser );
    foreach ( $reflection->getMethods() as $method ) {
      if ( !self::is_good_method( $method ) ) {
        continue;
      }
      $subcommand = self::create_subcommand( $container, false, $reflection->name, $method );
      $subcommand_name = $subcommand->get_name();
      $container->add_subcommand( $subcommand_name, $subcommand );
    }
    return $container;
  }

  private static function is_good_method( $method ) {
    return $method->isPublic() && !$method->isConstructor() && !$method->isStatic();
  }
}

