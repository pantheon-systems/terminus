<?php

use Terminus\Dispatcher;
use Terminus\Utils;
use Terminus\Exceptions\TerminusException;

class Help_Command extends TerminusCommand {

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
    $command = self::find_subcommand($args);

    if ($command) {
      $this->show_help($command);
      exit;
    }

    // WordPress is already loaded, so there's no chance we'll find the command
    if (function_exists('add_filter')) {
      throw new TerminusException("'{cmd}' is not a registered command.", array('cmd' => $args[0]));
    }
  }

  private static function find_subcommand($args) {
    $command = \Terminus::get_root_command();

    while (!empty($args) && $command && $command->can_have_subcommands()) {
      $command = $command->find_subcommand($args);
    }

    return $command;
  }

  /**
   * @param $command
   */
  private function show_help($command) {

    $out = self::get_initial_markdown($command);
    $longdesc = $command->get_longdesc();
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

    // section headers
    $out = preg_replace('/^## ([A-Z]+)/m', Terminus::colorize('%9\1%n'), $out);

    // definition lists
    $out = preg_replace_callback('/([^\n]+)\n: (.+?)(\n\n|$)/s', array(__CLASS__, 'rewrap_param_desc'), $out);

    $out = str_replace("\t", '  ', $out);

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
      Utils\is_windows()
      || in_array(Terminus::get_config('format'), array('bash', 'json')) 
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
    $name = implode(' ', Dispatcher\get_path($command));

    $binding = array(
      'name' => $name,
      'shortdesc' => $command->get_shortdesc(),
   );

    $binding['synopsis'] = wordwrap("$name " . $command->get_synopsis(), 79);

    if ($command->can_have_subcommands()) {
      $binding['has-subcommands']['subcommands'] = self::render_subcommands($command);
    }

    if (Terminus::get_config('format') == 'json') {
      $rendered_help = $binding;
    } else {
      $rendered_help = Utils\mustache_render('man.mustache', $binding);  
    }
    return $rendered_help;
  }

  private static function render_subcommands($command) {
    $subcommands = array();
    foreach ($command->get_subcommands() as $subcommand) {
      $subcommands[$subcommand->get_name()] = $subcommand->get_shortdesc();
    }

    if (Terminus::get_config('format') == 'json') {
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

Terminus::add_command('help', 'Help_Command');
