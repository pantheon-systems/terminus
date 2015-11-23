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
  function __invoke($args, $assoc_args) {
    $command = self::findSubcommand($args);

    if ($command) {
      $this->show_help($command);
      exit;
    }

    // WordPress is already loaded, so there's no chance we'll find the command
    if (function_exists('add_filter')) {
      $this->failure("'{cmd}' is not a registered command.", array('cmd' => $args[0]));
    }
  }

  private static function findSubcommand($args) {
    $command = Terminus::getRootCommand();

    while (!empty($args) && $command && $command->canHaveSubcommands()) {
      $command = $command->findSubcommand($args);
    }

    return $command;
  }

  /**
   * @param $command
   */
  private function show_help($command) {

    $out = self::get_initial_markdown($command);
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
      $out = preg_replace('/^## ([A-Z]+)/m', Terminus::colorize('%9\1%n'), $out);

      // definition lists
      $out = preg_replace_callback('/([^\n]+)\n: (.+?)(\n\n|$)/s', array(__CLASS__, 'rewrap_param_desc'), $out);

      $out = str_replace("\t", '  ', $out);
    }
    $this->pass_through_pager($out);
  }

    private static function rewrap_param_desc($matches) {
    $param = $matches[1];
    $desc = self::indent("\t\t", wordwrap($matches[2]));
    return "\t$param\n$desc\n\n";
  }

  private static function indent($whitespace, $text) {
    $lines = explode("\n", $text);
    foreach ($lines as &$line) {
      $line = $whitespace . $line;
    }
    return implode($lines, "\n");
  }

  private function pass_through_pager($out) {

    if (
      Utils\isWindows()
      || in_array(Terminus::getConfig('format'), array('bash', 'json')) 
    ) {
      // No paging for Windows cmd.exe; sorry
      $this->output()->outputValue($out);
      return 0;
    }

    // convert string to file handle
    $fd = fopen("php://temp", "r+");
    fputs($fd, $out);
    rewind($fd);

    $descriptorspec = array(
      0 => $fd,
      1 => STDOUT,
      2 => STDERR
   );

    return proc_close(proc_open('less -r', $descriptorspec, $pipes));
  }

  private static function get_initial_markdown($command) {
    $name = implode(' ', Dispatcher\getPath($command));

    $binding = array(
      'name' => $name,
      'shortdesc' => $command->getShortdesc(),
   );

    $binding['synopsis'] = wordwrap("$name " . $command->getSynopsis(), 79);

    if ($command->canHaveSubcommands()) {
      $binding['has-subcommands']['subcommands'] = self::render_subcommands($command);
    }

    if (Terminus::getConfig('format') == 'json') {
      $rendered_help = $binding;
    } else {
      $rendered_help = Utils\mustacheRender('man.mustache', $binding);  
    }
    return $rendered_help;
  }

  private static function render_subcommands($command) {
    $subcommands = array();
    foreach ($command->getSubcommands() as $subcommand) {
      $subcommands[$subcommand->getName()] = $subcommand->getShortdesc();
    }

    if (Terminus::getConfig('format') == 'json') {
      return $subcommands;
    }

    $max_len = self::get_max_len(array_keys($subcommands));
    $lines = array();
    foreach ($subcommands as $name => $desc) {
      $lines[] = str_pad($name, $max_len) . "\t\t\t" . $desc;
    }

    return $lines;
  }

  private static function get_max_len($strings) {
    $max_len = 0;
    foreach ($strings as $str) {
      $len = strlen($str);
      if ($len > $max_len)
        $max_len = $len;
    }

    return $max_len;
  }

}

Terminus::addCommand('help', 'HelpCommand');
