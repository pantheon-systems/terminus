<?php

namespace Terminus\Dispatcher;

use Terminus\DocParser;

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
   * @param CompositeCommand $parent    Parent command dispatcher object
   * @param string           $name      Name of command to run
   * @param DocParser        $docparser DocParser object for analysis of docs
   * @return CompositeCommand $this
   */
  public function __construct(CompositeCommand $parent, $name, DocParser $docparser) {
    $this->name      = $name;
    $this->parent    = $parent;
    $this->shortdesc = $docparser->getShortdesc();
    $this->longdesc  = $docparser->getLongdesc();
  }

  /**
   * Adds a subcommand to the subcommand array
   *
   * @param string           $name    Name of subcommand to add
   * @param CompositeCommand $command Command object to add
   * @return void
   */
  public function addSubcommand($name, CompositeCommand $command) {
    $this->subcommands[$name] = $command;
  }

  /**
   * Tells whether there can be subcommands of this object
   *
   * @return bool Always true
   */
  public function canHaveSubcommands() {
    return true;
  }

  /**
   * Finds and retrieves the subcommand from the first element of the param
   *
   * @param array $args An array of strings representing subcommand names
   * @return Subcommand|false
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
   * @return string
   */
  public function getLongdesc() {
    return $this->longdesc;
  }

  /**
   * Gets the name of the command this object represents
   *
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Gets the parent command object
   *
   * @return RootCommand
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Gets the short description of the command this object represents
   *
   * @return string
   */
  public function getShortdesc() {
    return $this->shortdesc;
  }

  /**
   * Sorts and retrieves the subcommands
   *
   * @return Subcommand[]
   */
  public function getSubcommands() {
    ksort($this->subcommands);

    return $this->subcommands;
  }

  /**
   * Gets the synopsis of the command this object represents
   *
   * @return string Always "<command>"
   */
  public function getSynopsis() {
    return '<command>';
  }

  /**
   * Gets the usage parameters of the command this object represents
   *
   * @param string $prefix Prefix to usage string
   * @return string
   */
  public function parseUsage($prefix) {
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
   * @param array $args       Array of command line non-params and non-flags
   * @param array $assoc_args Array of command line params and flags
   * @return string
   */
  public function invoke(array $args, array $assoc_args) {
    return $this->getUsage();
  }

  /**
   * Displays the usage parameters of the command this object represents
   *
   * @return string
   */
  public function getUsage() {
    $methods = $this->getSubcommands();

    $usage = '';
    if (!empty($methods)) {
      $subcommand = array_shift($methods);
      $usage = $subcommand->parseUsage('usage: ');
      foreach ($methods as $name => $subcommand) {
        $usage .= PHP_EOL . $subcommand->parseUsage('   or: ');
      }
    }
    $usage .= PHP_EOL . 'See "terminus help '. $this->name
      . ' <command>" for more information on a specific command.';
    return $usage;
  }

  /**
   * Retrieves aliases of a subcommand
   *
   * @param Subcommand[] $subcommands An array of subcommands, keyed by name
   * @return string[]
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
