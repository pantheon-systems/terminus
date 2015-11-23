<?php

namespace Terminus\Commands;

use Terminus;
use Terminus\Dispatcher;
use Terminus\Utils;
use Terminus\Commands\TerminusCommand;

class HelpCommand extends TerminusCommand {

  /**
   * Get help on a certain command.
   *
   * <command>
   *
   * ## EXAMPLES
   *
   *     # get help for `sites` command
   *     terminus help sites
   *
   *     # get help for `sites list` subcommand
   *     terminus help sites list
   *
   * @synopsis [<command>...]
   */
  public function __invoke($args, $assoc_args) {
    $command = $this->findSubcommand($args);

    if ($command) {
      $this->showHelp($command);
      exit;
    }

    // WordPress is already loaded, so there's no chance we'll find the command
    if (function_exists('add_filter')) {
      $this->failure(
        '"{cmd}" is not a registered command.',
        array('cmd' => $args[0])
      );
    }
  }

  /**
   * Finds a subcommand listed in the arguments, else returns the root command
   *
   * @param [array] $args Given command-line arguments
   * @return [mixed] $command
   */
  private function findSubcommand($args) {
    $command = Terminus::getRootCommand();

    while (!empty($args) && $command && $command->canHaveSubcommands()) {
      $command = $command->findSubcommand($args);
    }

    return $command;
  }

  /**
   * Retrieves the synopsis of a given command or subcommand
   *
   * @param [mixed] $command The command or subcommand to get documentation on
   * @return [string] $rendered_help
   */
  private function getInitialMarkdown($command) {
    $name = implode(' ', Dispatcher\getPath($command));

    $binding = array(
      'name' => $name,
      'shortdesc' => $command->getShortdesc(),
    );

    $binding['synopsis'] = wordwrap($name . ' ' . $command->getSynopsis(), 79);

    if ($command->canHaveSubcommands()) {
      $binding['has-subcommands']['subcommands'] =
        $this->renderSubcommands($command);
    }

    if (Terminus::getConfig('format') == 'json') {
      $rendered_help = $binding;
    } else {
      $rendered_help = Utils\mustacheRender('man.mustache', $binding);
    }
    return $rendered_help;
  }

  /**
   * Counts the length of the given strings and returns their maximum value
   *
   * @param [array] $strings An array of strings
   * @return [integer] $max_length
   */
  private function getMaximumLength($strings) {
    $max_length = 0;
    foreach ($strings as $string) {
      $length = strlen($string);
      if ($length > $max_length) {
        $max_length = $length;
      }
    }

    return $max_length;
  }

  /**
   * Intents each new line in the text with the given whitespace string
   *
   * @param [string] $text       New line-delineated information to intent
   * @param [string] $whitespace Whitespace string to use in new lines of text
   * @return [string] $indented_lines
   */
  private function indent($text, $whitespace = "\t\t") {
    $lines = explode("\n", $text);
    foreach ($lines as $index => $line) {
      $lines[$index] = $whitespace . $line;
    }
    $indented_lines = implode($lines, "\n");
    return $indented_lines;
  }

  /**
   * Displays the output with Less
   *
   * @param [string] $out Help text to be displayed
   * @return [integer] $exit_status Exit status of Less
   */
  private function passThroughPager($out) {
    if (Utils\isWindows()
      || in_array(Terminus::getConfig('format'), array('bash', 'json'))
    ) {
      // No paging for Windows cmd.exe; sorry
      $this->output()->outputValue($out);
      return 0;
    }

    // convert string to file handle
    $fd = fopen('php://temp', 'r+;');
    fputs($fd, $out);
    rewind($fd);

    $descriptorspec = array(
      0 => $fd,
      1 => STDOUT,
      2 => STDERR
    );

    $exit_status = proc_close(proc_open('less -r', $descriptorspec, $pipes));
    return $exit_status;
  }

  /**
   * Gets the basic descriptions of a command's subcommands from internal docs
   *
   * @param [CompositeCommand] $command The command of which to get subcommands
   * @return [array] $lines An array of stringified subcommands of the command
   */
  private function renderSubcommands($command) {
    $subcommands = array();
    foreach ($command->getSubcommands() as $subcommand) {
      $subcommands[$subcommand->getName()] = $subcommand->getShortdesc();
    }

    if (Terminus::getConfig('format') == 'json') {
      return $subcommands;
    }

    $max_len = $this->getMaximumLength(array_keys($subcommands));
    $lines   = array();
    foreach ($subcommands as $name => $desc) {
      $lines[] = str_pad($name, $max_len) . "\t\t\t" . $desc;
    }

    return $lines;
  }

  /**
   * Formats the description of the parameter
   *
   * @param [array] $matches An array of strings of parameters of a subcommand
   * @return [string] $description A formatted string of the parameters
   */
  private function rewrapParameterDescription($matches) {
    $param       = $matches[1];
    $desc        = $this->indent(wordwrap($matches[2]));
    $description = "\t$param\n$desc\n\n";
    return $description;
  }

  /**
   * Takes a command to get help for and processes its internal documentation
   *
   * @param [mixed] $command The command to offer help for
   * @return [void]
   */
  private function showHelp($command) {
    $out      = $this->getInitialMarkdown($command);
    $longdesc = $command->getLongdesc();
    if ($longdesc) {
      if (is_array($longdesc)) {
        $flag_list = array_pop($longdesc);
        $flags     = array();
        foreach ($flag_list as $desc) {
          $flags[$desc['synopsis']] = $desc['desc'];
        }
        $out['parameters'] = $flags;
        $out = json_encode($out);
      } else {
        $out .= wordwrap($longdesc, 79) . "\n";
      }
    }

    if (is_string($out)) {
      // section headers
      $out = preg_replace(
        '/^## ([A-Z]+)/m',
        Terminus::colorize('%9\1%n'),
        $out
      );

      // definition lists
      $out = preg_replace_callback(
        '/([^\n]+)\n: (.+?)(\n\n|$)/s',
        array(__CLASS__, 'rewrapParameterDescription'),
        $out
      );

      $out = str_replace("\t", '  ', $out);
    }
    $this->passThroughPager($out);
  }

}

Terminus::addCommand('help', 'HelpCommand');
