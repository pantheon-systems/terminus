<?php

namespace Terminus;

use Terminus;

class Completions {

  /**
   * @var array
   */
  private $opts = array();
  /**
   * @var array
   */
  private $words;

  /**
   * Constructs object, parses command
   *
   * @param string $line Command to make completions for
   */
  public function __construct($line) {
    // TODO: properly parse single and double quotes
    $this->words = explode(' ', $line);

    if ($this->words[0] == 'terminus') {
      array_shift($this->words);
    }

    //The last word is either empty or an incomplete subcommand
    $this->cur_word = end($this->words);

    $r = $this->getCommand($this->words);
    if (!is_array($r)) {
      return;
    }

    /** @var \Terminus\Dispatcher\RootCommand $command */
    list($command, $args, $assoc_args) = $r;

    $spec = SynopsisParser::parse($command->getSynopsis());

    foreach ($spec as $arg) {
      if ($arg['type'] == 'positional' && $arg['name'] == 'file') {
        $this->add('<file> ');
        return;
      }
    }

    if ($command->canHaveSubcommands()) {
      foreach ($command->getSubcommands() as $name => $_) {
        $this->add("$name ");
      }
    } else {
      foreach ($spec as $arg) {
        if (in_array($arg['type'], array('flag', 'assoc'))) {
          if (isset($assoc_args[ $arg['name'] ])) {
            continue;
          }

          $opt = "--{$arg['name']}";

          if ($arg['type'] == 'flag') {
            $opt .= ' ';
          } elseif (!$arg['value']['optional']) {
            $opt .= '=';
          }

          $this->add($opt);
        }
      }
    }
  }

  /**
   * Prints out all opt elements on their own lines
   *
   * @return void
   */
  public function render() {
    foreach ($this->opts as $opt) {
      Terminus::getOutputter()->line($opt);
    }
  }

  /**
   * Adds options to opts array
   *
   * @param string $opt Option to add
   * @return void
   */
  private function add($opt) {
    if ($this->cur_word !== '') {
      if (strpos($opt, $this->cur_word) === 0) {
        return;
      }
    }

    $this->opts[] = $opt;
  }

  /**
   * Gets command to run
   *
   * @param array $words Words of the command-line string to process
   * @return array $command_array
   *         [RootCommand] $command
   *         [array]  $args
   *         [array]  $assoc_args
   */
  private function getCommand($words) {
    $positional_args = $assoc_args = array();

    foreach ($words as $arg) {
      if (preg_match('|^--([^=]+)=?|', $arg, $matches)) {
        $assoc_args[$matches[1]] = true;
      } else {
        $positional_args[] = $arg;
      }
    }

    $r = Terminus::getRunner()->findCommandToRun($positional_args);
    if (!is_array($r) && array_pop($positional_args) == $this->cur_word) {
      $r = Terminus::getRunner()->findCommandToRun($positional_args);
    }

    if (!is_array($r)) {
      return $r;
    }

    list($command, $args) = $r;

    return array($command, $args, $assoc_args);
  }

}
