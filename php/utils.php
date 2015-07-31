<?php

namespace Terminus\Utils;

use \Terminus\Dispatcher;
use \Terminus\Iterators\Transform;
use \ArrayIterator;

if(!defined('JSON_PRETTY_PRINT')) {
  define('JSON_PRETTY_PRINT', 128);
}

/**
 * Composes positional arguments into a command string.
 *
 * @param [array] $args Array of arguments
 * @return [string] $command_string Param rendered into command string
 */
function args_to_str($args) {
  $command_string = ' ' . implode(' ', array_map('escapeshellarg', $args));
  return $command_string;
}

/**
 * Composes associative arguments into a command string
 *
 * @param [array] $assoc_args Arguments for command line in array form
 * @return [string] $return Command string form of param
 */
function assoc_args_to_str($assoc_args) {
  $return = '';

  foreach ($assoc_args as $key => $value) {
    if($value === true) {
      $return .= " --$key";
    } else {
      $return .= " --$key=" . escapeshellarg($value);
    }
  }

  return $return;
}

/**
 * Outputs array as string, space-separated
 *
 * @param [array] $array Array to be stringified
 * @return [string] $output Param array imploded with spaces
 */
function bash_out($array) {
  $output = '';
  foreach($array as $index => $row) {
    if(is_array($row) OR is_object($row)) {
      $row = (array)$row;
      $row = join(' ', $row);
    }
    if(!is_numeric($index)) {
      $output .= "$index ";
    }
    $output .= $row . PHP_EOL;
  }
  return $output;
}

/**
 * Ensures that the given destination is valid
 *
 * @param [string]  $destination Location of directory to ensure viability of
 * @param [boolean] $make        True to create destination if it does not exist
 * @return [string] $destination Same as the parameter
 */
function destination_is_valid($destination, $make = true) {
  if (file_exists($destination) AND !is_dir($destination)) {
    \Terminus::error("Destination given is a file. It must be a directory.");
  }

  if (!is_dir($destination)) {
    if(!$make) {
      $make = \Terminus::confirm("Directory does not exists. Create it now?");
    }
    if($make) {
      mkdir($destination, 0755);
    }
  }

  return $destination;
}

/**
 * Given a template string and an arbitrary number of arguments,
 * returns the final command, with the parameters escaped.
 *
 * @param [string] $cmd Command to escape the parameters of
 * @return [string] $final_cmd Parameter-escaped command
 */
function esc_cmd($cmd) {
  if(func_num_args() < 2) {
    trigger_error('esc_cmd() requires at least two arguments.', E_USER_WARNING);
  }
  $args      = func_get_args();
  $cmd       = array_shift($args);
  $final_cmd = vsprintf($cmd, array_map('escapeshellarg', $args));
  return $final_cmd;
}

/**
 * Search for file by walking up the directory tree until the first file is
 * found or until $stop_check($dir) returns true
 *
 * @param [array]    $files      The file(s) to search for
 * @param [string]   $dir        The directory to start searching from,
 *                                 defaults to CWD
 * @param [function] $stop_check Passed the current dir each time a directory
 *                                 level is traversed
 * @return [string] $path File name if found, null if the file was not found
 */
function find_file_upward($files, $dir = null, $stop_check = null) {
  $files = (array)$files;
  if(is_null($dir)) {
    $dir = getcwd();
  }
  while(is_readable($dir)) {
    //Stop walking when the supplied callable returns true being passed the $dir
    if(is_callable($stop_check) && call_user_func($stop_check, $dir)) {
      return null;
    }

    foreach ($files as $file) {
      $path = $dir . DIRECTORY_SEPARATOR . $file;
      if(file_exists($path)) {
        return $path;
      }
    }

    $parent_dir = dirname($dir);
    if(empty($parent_dir) || ($parent_dir === $dir)) {
      break;
    }
    $dir = $parent_dir;
  }
  return null;
}

/**
 * Output items in a table, JSON, CSV, IDs, or the total count
 *
 * @param [string] $format Format to use: 'table', 'json', 'csv', 'ids', 'count'
 * @param [array]  $items  Data to output
 * @param [array]  $fields Named fields for each datum,
 *                           array or comma-separated string
 * @return [void]
 */
function format_items($format, $items, $fields) {
  $assoc_args = array(
    'format' => $format,
    'fields' => $fields
  );
  $formatter  = new \Terminus\Formatter($assoc_args);
  $formatter->display_items($items);
}

/**
 * Get file name from a URL
 *
 * @param [string] $url A valid URL
 * @return [string] The file name from the given URL
 */
function get_filename_from_url($url) {
  $path     = parse_url($url);
  $parts    = explode('/', $path['path']);
  $filename = end($parts);
  return $filename;
}

/**
 * Return an array of paths where vendor autoload files may be located
 *
 * @return [array] $vendor_paths
 */
function get_vendor_paths() {
  $vendor_paths = array(
    TERMINUS_ROOT . '/../../../vendor',
    TERMINUS_ROOT . '/vendor'
  );
  return $vendor_paths;
}

/**
 * Processes exception message and throws it
 *
 * @param [Exception] $exception Exception object thrown
 * @return [void]
 */
function handle_exception($exception) {
  $trace = $exception->getTrace();
  if(!empty($trace) AND \Terminus::get_config('verbose')) {
    foreach($exception->getTrace() as $line) {
      $out_line = sprintf(
        "%s%s%s [%s:%s]",
        $line['class'],
        $line['type'],
        $line['function'],
        $line['file'],
        $line['line']
      );
      \Terminus\Loggers\Regular::redLine(">> $out_line");
    }
  }
  \Terminus::error("Exception thrown - %s", array($exception->getMessage()));
}

/**
 * Checks to see whether TERMINUS_HOST is pointed at Hermes
 *
 * @return [boolean] $is_hermes True if Terminus is operating on Hermes
 */
function is_hermes() {
  $is_hermes = (TERMINUS_HOST == 'dashboard.getpantheon.com');
  return $is_hermes;
}

/**
 * Checks given path for whether it is absolute
 *
 * @param [string] $path Path to check
 * @return [boolean] $is_root True if path is absolute
 */
function is_path_absolute($path) {
  $is_root = (isset($path[1]) && ($path[1] == ':') || ($path[0] == '/'));
  return $is_root;
}

/**
 * Checks whether email is in a valid or not
 *
 * @param [string] $email String to be evaluated for email address format
 * @return [boolean] $is_email True if $email is in email address format
 */
function is_valid_email($email) {
  $is_email = filter_var($email, FILTER_VALIDATE_EMAIL);
  return (boolean)$is_email;
}

/**
 * Validates an Atlas UUID
 *
 * @param [string] $uuid String to check for being a valid Atlas UUID
 * @return [boolean] True if string is a valid UUID
 */
function is_valid_uuid($uuid) {
  $regex   = '#^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$#';
  $is_uuid = preg_match($regex, $uuid);
  return (boolean)$is_uuid;
}

/**
 * Check whether Terminus is running in a Windows environment
 *
 * @return [boolean] $is_windows True if OS running Terminus is Windows
 */
function is_windows() {
  $is_windows = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN');
  return $is_windows;
}

/**
 * Like array_map() except it returns a new iterator instead of a modified array
 *
 * Example:
 *
 *     $arr = array('Football', 'Socker');
 *
 *     $it = iterator_map($arr, 'strtolower', function($val) {
 *       return str_replace('foo', 'bar', $val);
 *     });
 *
 *     foreach ( $it as $val ) {
 *       var_dump($val);
 *     }
 *
 * @param [array]    $iterator Either a plain array or another iterator
 * @param [function] $function The function to apply to an element
 * @return [object] $iterator An iterator that applies the given callback(s)
 */
function iterator_map($iterator, $function) {
  if(is_array($iterator)) {
    $iterator = new \ArrayIterator($iterator);
  }

  if(!method_exists($iterator, 'add_transform')) {
    $iterator = new Transform($iterator);
  }

  foreach(array_slice(func_get_args(), 1) as $function) {
    $iterator->add_transform($function);
  }

  return $iterator;
}

/**
 * Returns the var string in JSON format
 *
 * @param [string] $var String to be turned into JSON
 * @return [string] $jsonified JSONified param
 */
function json_dump($var) {
  if (\cli\Shell::isPiped()) { //If it's a piped command, don't prettify JSON.
    $jsonified = json_encode($var);
  } else { //If it's not, make it legible to humans.
    $jsonified = json_encode($var, JSON_PRETTY_PRINT) . "\n";
  }
  return $jsonified;
}

/**
 * Includes every command file in the commands directory
 *
 * @return [void]
 */
function load_all_commands() {
  $cmd_dir = TERMINUS_ROOT . '/php/commands';

  $iterator = new \DirectoryIterator($cmd_dir);

  foreach($iterator as $filename) {
    if(substr($filename, -4) != '.php') {
      continue;
    }

    include_once "$cmd_dir/$filename";
  }
}

/**
 * Includes a single command file
 *
 * @param [string] $name Of command of which the class file will be included
 * @return [void]
 */
function load_command($name) {
  $path = TERMINUS_ROOT . "/php/commands/$name.php";

  if(is_readable($path)) {
    include_once $path;
  }
}

/**
 * Requires inclusion of Composer's autoload file
 *
 * @return [void]
 */
function load_dependencies() {
  if(strpos(TERMINUS_ROOT, 'phar:') === 0) {
    require TERMINUS_ROOT . '/vendor/autoload.php';
    return;
  }

  $has_autoload = false;

  foreach(get_vendor_paths() as $vendor_path) {
    if(file_exists($vendor_path . '/autoload.php') ) {
      require $vendor_path . '/autoload.php';
      $has_autoload = true;
      break;
    }
  }

  if (!$has_autoload) {
    fputs(STDERR, "Internal error: Can't find Composer autoloader.\n");
    exit(3);
  }
}

/**
 * Using require() directly inside a class grants access to private methods
 * to the loaded code
 *
 * @param [string] $path Path to the file to be required
 * @return [void]
 */
function load_file($path) {
  require $path;
}

/**
 * Launch system's $EDITOR to edit text
 *
 * @param [string] $input Text to be put into the temp file for changing
 * @param [string] $title Name for the temporary file
 * @return [string] $output Output string if input has changed, false otherwise
 */
function launch_editor_for_input($input, $title = 'Terminus') {
  $tmpfile = wp_tempnam($title);
  if(!$tmpfile) {
    \Terminus::error('Error creating temporary file.');
  }

  $output = '';
  file_put_contents($tmpfile, $input);

  $editor = getenv('EDITOR');
  if(!$editor) {
    if(isset($_SERVER['OS']) && (strpos($_SERVER['OS'], 'indows') !== false)) {
      $editor = 'notepad';
    } else {
      $editor = 'vi';
    }
  }

  \Terminus::launch("$editor " . escapeshellarg($tmpfile));
  $output = file_get_contents($tmpfile);
  unlink($tmpfile);

  if ($output == $input) {
    return false;
  }

  return $output;
}

/**
 * Creates progress bar for operation in progress
 *
 * @param [string]  $message Message to be displayed with the progress bar
 * @param [integer] $count   Number of progress dots to be displayed
 * @return [\cli\progress\Bar] $progress_bar Object which handles display of bar
 */
function make_progress_bar($message, $count) {
  if(\cli\Shell::isPiped()) {
    return new \Terminus\NoOp;
  }

  $progress_bar = new \cli\progress\Bar($message, $count);
  return $progress_bar;
}

/**
 * Render PHP or other types of files using Mustache templates
 * IMPORTANT: Automatic HTML escaping is disabled!
 *
 * @param [string] $template_name File name of the template to be used
 * @param [array]  $data          Context to pass through for template use
 * @return [string] $rendered_template The rendered template
 */
function mustache_render($template_name, $data) {
  if (!file_exists($template_name)) {
    $template_name = TERMINUS_ROOT . "/templates/$template_name";
  }

  $template = file_get_contents($template_name);

  $mustache = new \Mustache_Engine(
    array(
      'escape' => function ($val) {
        return $val;
      }
    )
  );
  $rendered_template = $mustache->render($template, $data);
  return $rendered_template;
}

/**
 * Takes a host string such as from wp-config.php and parses it into an array
 *
 * @param [string] $raw_host MySQL host string, as defined in wp-config.php
 * @return [array] $assoc_args Connection inforrmation for MySQL
 */
function mysql_host_to_cli_args($raw_host) {
  $assoc_args = array();

  $host_parts = explode(':', $raw_host);
  if (count($host_parts) == 2) {
    list($assoc_args['host'], $extra) = $host_parts;
    $extra = trim($extra);
    if (is_numeric($extra)) {
      $assoc_args['port']     = intval($extra);
      $assoc_args['protocol'] = 'tcp';
    } elseif($extra !== '') {
      $assoc_args['socket'] = $extra;
    }
  } else {
    $assoc_args['host'] = $raw_host;
  }

  return $assoc_args;
}

/**
 * Parses a URL and returns its components
 * Overrides native PHP parse_url(string)
 *
 * @param [string] $url URL to parse
 * @return [array] $url_parts An array of URL components
 */
function parse_url($url) {
  $url_parts = \parse_url($url);

  if(!isset($url_parts['scheme'])) {
    $url_parts = parse_url('http://' . $url);
  }

  return $url_parts;
}

/**
 * Pick fields from an associative array or object.
 *
 * @param [array] $item   Associative array or object to pick fields from
 * @param [array] $fields List of fields to pick
 * @return [array] $values
 */
function pick_fields($item, $fields) {
  $item   = (object)$item;
  $values = array();

  foreach ($fields as $field) {
    $values[$field] = isset($item->$field) ? $item->$field : null;
  }

  return $values;
}

/**
 * Replaces directory structure constants in PHP source code
 *
 * @param [string] $source The PHP code to manipulate
 * @param [string] $path   The path to use instead of path constants
 * @return [string] $altered_source Source with constants replaced
 */
function replace_path_consts($source, $path) {
  $replacements = array(
    '__FILE__' => "'$path'",
    '__DIR__'  => "'" . dirname($path) . "'"
  );

  $old = array_keys($replacements);
  $new = array_values($replacements);
  $altered_source = str_replace($old, $new, $source);
  return $altered_source;
}

/**
 * Fetches keys from the first object in a collection
 *
 * @param [array] $result Data for cURLing
 * @return [array] $keys Array keys of first object in param $result
 */
function result_get_response_fields($result) {
  $iter = new ArrayIterator($result);
  if(!$iter) {
    return false;
  }
  $keys = array_keys((array)$iter->current());
  $keys = array_map('ucfirst', $keys);
  unset($iter);
  return $keys;
}

/**
 * Checks whether param is an array of multiple objects or of one
 *
 * @param [array] $array Array to evaluate
 * @return [boolean] True if first element in array is an object or an array
 */
function result_is_multiobj($array) {
  $iter = new ArrayIterator($array);
  if(is_object($iter->current()) || is_array($iter->current())) {
    return true;
  }
  unset($iter);
  return false;
}

/**
 * Runs the given MySQL statement
 *
 * @param [string] $cmd         MySQL statement to be executed
 * @param [array]  $assoc_args  Conditions to be formatted to use with statement
 * @param [array]  $descriptors Any of: opened file, open socket, input method
 * @return [void]
 */
function run_mysql_command($cmd, $assoc_args, $descriptors = null ) {
  if(!$descriptors) {
    $descriptors = array(STDIN, STDOUT, STDERR);
  }

  if(isset($assoc_args['host'])) {
    $assoc_args = array_merge(
      $assoc_args,
      mysql_host_to_cli_args($assoc_args['host'])
    );
  }

  $env = (array)$_ENV;
  if(isset($assoc_args['pass'])) {
    $env['MYSQL_PWD'] = $assoc_args['pass'];
    unset($assoc_args['pass']);
  }

  $final_cmd = $cmd . assoc_args_to_str($assoc_args);

  $proc = proc_open($final_cmd, $descriptors, $pipes, null, $env);
  if(!$proc) {
    exit(1);
  }

  $status = proc_close($proc);

  if($status) {
    exit($status);
  }
}

/**
 * Sanitize the site name field
 *
 * @param [string] $string String to be sanitized
 * @return [string] $name Param string, sanitized
 */
function sanitize_name($string) {
  $name = $string;
  // squash whitespace
  $name = trim(preg_replace('#\s+#', ' ', $name));
  // replace spacers with hyphens
  $name = preg_replace("#[\._ ]#", "-", $name);
  // crush everything else
  $name = strtolower(preg_replace("#[^A-Za-z0-9-]#", "", $name));
  return $name;
}

/**
 * Removes ".gz" from a filename
 *
 * @param [string] $filename Name of file from which to remove ".gz"
 * @return [string] $file Param string, ".gz" removed
 */
function sql_from_zip($filename) {
  $file = preg_replace('#\.gz$#s', '', $filename);
  return $file;
}

/**
 * Write data as CSV to a given file
 *
 * @param [resource] $file_descriptor File descriptor
 * @param [array]    $rows            Array of rows to output
 * @param [array]    $headers         List of CSV columns
 * @return [void]
 */
function write_csv($file_descriptor, $rows, $headers = array()) {
  if(!empty($headers)) {
    fputcsv($file_descriptor, $headers);
  }

  foreach($rows as $row) {
    if(!empty($headers)) {
      $row = pick_fields($row, $headers);
    }

    fputcsv($file_descriptor, array_values($row));
  }
}
