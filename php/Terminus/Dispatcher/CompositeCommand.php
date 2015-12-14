<?php

namespace Terminus\Dispatcher;

use Terminus;

/**
 * A non-leaf node in the command tree.
 */
class CompositeCommand {
  protected $name;
  protected $parent;
  protected $shortdesc;
  protected $subcommands = array();
  protected $synopsis;

  /**
   * Object constructor. Sets object properties
   *
   * @param [RootCommand] $parent    Parent command dispatcher object
   * @param [string]      $name      Name of command to run
   * @param [DocParser]   $docparser DocParser object for analysis of docs
   * @return [CompositeCommand] $this
   */
  public function __construct($parent, $name, $docparser) {
    $this->name      = $name;
    $this->parent    = $parent;
    $this->shortdesc = $docparser->getShortdesc();
    $this->longdesc  = $docparser->getLongdesc();
  }

  /**
   * Adds a subcommand to the subcommand array
   *
   * @param [string]     $name    Name of subcommand to add
   * @param [Subcommand] $command Subcommand object to add
   * @return [void]
   */
  public function addSubcommand($name, $command) {
    $this->subcommands[$name] = $command;
  }

  /**
   * Tells whether there can be subcommands of this object
   *
   * @return [boolean] Always true
   */
  public function canHaveSubcommands() {
    return true;
  }

  /**
   * Finds and retrieves the subcommand from the first element of the param
   *
   * @param [array] $args An array of strings representing subcommand names
   * @return [mixed] $subcommands[$name] or false if DNE
   */
  public function findSubcommand(&$args) {
    $name        = array_shift($args);
    $subcommands = $this->getSubcommands();

    if (!isset($subcommands[$name])) {
      $aliases = self::getAliases($subcommands);
      if (isset($aliases[$name])) {
        $name = $aliases[$name];
      }
    }

    if (!isset($subcommands[$name])) {
      return false;
    }

    return $subcommands[$name];
  }

  /**
   * Gets the long description of the command this object represents
   *
   * @return [string] $this->longdesc
   */
  public function getLongdesc() {
    return $this->longdesc;
  }

  /**
   * Gets the name of the command this object represents
   *
   * @return [string] $this->name
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the parent command object
   *
   * @return [RootCommand] $this->parent
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Gets the short description of the command this object represents
   *
   * @return [string] $this->shortdesc
   */
  public function getShortdesc() {
    return $this->shortdesc;
  }

  /**
   * Sorts and retrieves the subcommands
   *
   * @return [array] $this->subcommands Array of Subcommand objects
   */
  public function getSubcommands() {
    ksort($this->subcommands);

    return $this->subcommands;
  }

  /**
   * Gets the synopsis of the command this object represents
   *
   * @return [string] Always "<command>"
   */
  public function getSynopsis() {
    return '<command>';
  }

  /**
   * Gets the usage parameters of the command this object represents
   *
   * @param [string] $prefix Prefix to usage string
   * @return [string] $usage
   */
  public function getUsage($prefix) {
    $usage = sprintf(
      '%s%s %s',
      $prefix,
      implode(' ', getPath($this)),
      $this->getSynopsis()
    );
    return $usage;
  }

  /**
   * Displays the usage parameters of the command this object represents
   *
   * @param [array] $args       Array of command line non-params and non-flags
   * @param [array] $assoc_args Array of command line params and flags
   * @return [void]
   */
  public function invoke($args, $assoc_args) {
    $this->showUsage();
  }

  /**
   * Displays the usage parameters of the command this object represents
   *
   * @return [void]
   */
  public function showUsage() {
    $methods = $this->getSubcommands();

    if (!empty($methods)) {
      $subcommand = array_shift($methods);
      Terminus::line($subcommand->getUsage('usage: '));
      foreach ($methods as $name => $subcommand) {
        Terminus::line($subcommand->getUsage('   or: '));
      }
    }
    Terminus::line();
    Terminus::line(
      'See "terminus help '
      . $this->name
      . '<command>" for more information on a specific command.'
    );
  }

  /**
   * Retrieves aliases of a subcommand
   *
   * @param [array] $subcommands An array of subcommandname strings
   * @return [array] $aliases An array of alias strings
   */
  private static function getAliases($subcommands) {
    $aliases = array();
    foreach ($subcommands as $name => $subcommand) {
      $alias = $subcommand->getAlias();
      if ($alias) {
        $aliases[$alias] = $name;
      }
    }
    return $aliases;
  }

}
