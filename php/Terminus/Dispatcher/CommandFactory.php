<?php

namespace Terminus\Dispatcher;

use Terminus\DocParser;
use Terminus\Dispatcher\CompositeCommand;

/**
 * Creates CompositeCommand or Subcommand instances.
 */
class CommandFactory {

  /**
   * Creates a new composite command or subcommand
   *
   * @param string           $class   Name of class command belongs to
   * @param CompositeCommand $parent  Parent command
   * @param array            $options Options to feed into the called command
   * @return CompositeCommand
   */
  public static function create(
    $class,
    CompositeCommand $parent,
    $options
  ) {
    $reflection = new \ReflectionClass($class);
    $docparser = new DocParser($reflection->getDocComment());

    if ($name = $docparser->getTag('command')) {
      if ($reflection->hasMethod('__invoke')) {
        $command = self::createSubcommand(
          $parent,
          $name,
          $reflection->name,
          $reflection->getMethod('__invoke'),
          $options
        );
      } else {
        $command = self::createCompositeCommand(
          $parent,
          $name,
          $reflection,
          $options
        );
      }

      $parent->addSubcommand($name, $command);
    }
    // @TODO: If we are in developer mode, warn that the command was ill-formed.
  }

  /**
   * Creates a new composite command
   *
   * @param CompositeCommand $parent     Parent command
   * @param string           $name       Name of command to create
   * @param \ReflectionClass $reflection Object with name of class to call
   * @param array            $options    Options to feed into a called command
   * @return CompositeCommand
   */
  private static function createCompositeCommand(
    CompositeCommand $parent,
    $name,
    \ReflectionClass $reflection,
    $options
  ) {
    $docparser = new DocParser($reflection->getDocComment());

    // If the composite command already exists, don't recreate it.
    // This allows plugins to add to existing commands.
    $args = array($name);
    $container = $parent->findSubcommand($args);
    if (!$container) {
      $container = new CompositeCommand($parent, $name, $docparser);
    }

    foreach ($reflection->getMethods() as $method) {
      if (!self::isGoodMethod($method)) {
        continue;
      }
      $subcommand      = self::createSubcommand(
        $container,
        false,
        $reflection->name,
        $method,
        $options
      );
      $subcommand_name = $subcommand->getName();
      $container->addSubcommand($subcommand_name, $subcommand);
    }

    return $container;
  }

  /**
   * Creates a new subcommand
   *
   * @param CompositeCommand  $parent     Parent command
   * @param string            $name       Name of command to create
   * @param string            $class_name Name of class command belongs to
   * @param \ReflectionMethod $method     Name of function to invoke in class
   * @param array             $options    Options to feed into the a command
   * @return Subcommand
   */
  private static function createSubcommand(
    CompositeCommand $parent,
    $name,
    $class_name,
    \ReflectionMethod $method,
    $options
  ) {
    $docparser = new DocParser($method->getDocComment());
    if (!$name) {
      $name = $docparser->getTag('subcommand');
      if (!$name) {
        $name = $method->name;
      }
    }
    $method_name  = $method->name;
    $when_invoked =
      function ($args, $assoc_args) use (
        $class_name,
        $method_name,
        $options
      ) {
        call_user_func(
          array(new $class_name($options), $method_name),
          $args,
          $assoc_args
        );
      };

    $subcommand = new Subcommand(
      $parent,
      $name,
      $docparser,
      $when_invoked,
      $options
    );
    return $subcommand;
  }

  /**
   * Decides if the given method is a good one
   *
   * @param \ReflectionMethod $method Method to be called
   * @return bool
   */
  private static function isGoodMethod(\ReflectionMethod $method) {
    $is_good_method = $method->isPublic()
      && !$method->isConstructor()
      && !$method->isStatic()
      && (strpos($method->getDocComment(), '@non-command') === false);
    return $is_good_method;
  }

}

