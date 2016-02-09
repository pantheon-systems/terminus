<?php

namespace Terminus;

use Terminus\Runner;

class Completions {

  /**
   * @var array
   */
  private $options = [];
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

          $option = "--{$arg['name']}";

          if ($arg['type'] == 'flag') {
            $option .= ' ';
          } elseif (!$arg['value']['optional']) {
            $option .= '=';
          }

          $this->add($option);
        }
      }
    }
  }

  /**
   * Returns the options property
   *
   * @return array
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * Adds options to options array
   *
   * @param string $option Option to add
   * @return void
   */
  private function add($option) {
    if ($this->cur_word !== '') {
      if (strpos($option, $this->cur_word) === 0) {
        return;
      }
    }

    $this->options[] = $option;
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

    $runner             = new Runner();
    $command_components = $runner->findCommandToRun($positional_args);
    if (!is_array($command_components)
      && array_pop($command_components) == $this->cur_word
    ) {
      $command_components = $runner->findCommandToRun($positional_args);
    }

    if (is_array($command_components)) {
      $command_components[] = $assoc_args;
    }

    return $command_components;
  }

}
