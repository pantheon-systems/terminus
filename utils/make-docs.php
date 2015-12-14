<?php

define('TERMINUS_ROOT', dirname(__DIR__));
require(TERMINUS_ROOT . '/php/utils.php');
require(TERMINUS_ROOT . '/vendor/autoload.php');

use Terminus\Utils;

/**
 * Accepts the an array representing a tokenized PHP file and sifts out all
 * sets of tokens meeting a specific pattern
 *
 * @param [array] $tokens  A tokenized PHP file
 * @param [array] $pattern Token name pattern to search out
 * @return [array] $matching_patterns
 */
function findTokenPatterns(
    $tokens,
    $pattern   = array('T_DOC_COMMENT', 'T_PUBLIC', 'T_FUNCTION', 'T_STRING')
  ) {
  $matching_patterns = array();
  while (!empty($tokens)) {
    $token           = array_shift($tokens);
    $pattern_pointer = 0;
    if (is_array($token) && (token_name($token[0]) == $pattern[0])) {
      do {
        if (token_name($token[0]) == $pattern[$pattern_pointer]) {
          $match[token_name($token[0])] = $token[1];
          $pattern_pointer++;
        }
        if ($pattern_pointer == count($pattern)) {
          $matching_patterns[$token[1]] = $match;
          break;
        }
        $token = array_shift($tokens);
      } while (is_integer($token[0]) && (token_name($token[0]) != $pattern[0]));
    }
  }
  return $matching_patterns;
}

/**
 * Retrieves all file names from a directory
 *
 * @param [string] $dir_name Name of the directory from which to extract file names
 * @return [array <String>] $file_list
 */
function getFiles($dir_name) {
  $dir_files = scandir($dir_name);
  $file_list = array();
  foreach ($dir_files as $file_name) {
    $file = "$dir_name/$file_name";
    if (is_file($file)) {
       $file_list[] = $file;
    } else if (($file_name[0] != '.') && is_dir($file)) {
      $file_list = array_merge(getFiles($file), $file_list);
    }
  }
  return $file_list;
}

/**
 * Uses PHP Tokenizer to extract documentation from the PHP files
 *
 * @param [string] $file_name Name of the file to read and tokenize
 * @return [array] $tokens
 */
function getTokens($file_name) {
  $file_contents = file_get_contents($file_name);
  $tokens        = token_get_all($file_contents);
  return $tokens;
}

/**
 * Parses PHP internal documentation into chunks
 *
 * @param [string] $doc_string The raw doc string from the PHP file
 * @return [array] $parsed_doc
 */
function parseDocs($doc_string) {
  $exploded_docs = explode("\n", $doc_string);
  $lines         = array();
  foreach ($exploded_docs as $doc_line) {
    $line = trim(str_replace(array('/**', '*/', '*'), '', trim($doc_line)));
    if (!empty($line)) {
      $lines[] = $line;
    }
  }
  $parsed_doc = array('description' => array(), 'param' => array(), 'return' => array());
  $current    = 'description';
  do {
    $line = array_shift($lines);
    if ($line[0] == '@') {
      $breakdown = explode(' ', $line);
      $current   = substr($breakdown[0], 1);
      unset($breakdown[0]);
      $line = implode(' ', $breakdown);
    } else if ($current != 'description') {
      $line = "-$line";
    }
    $parsed_doc[$current][] = $line;
  } while (!empty($lines));
  return $parsed_doc;
}

/**
 * Writes documentation to file
 *
 * @param [string]         $namespace Namespace to which the doc file will pertain
 * @param [array <string>] $docs      Documentation to add to file
 * @return [boolean] True if write was successful
 */
function writeDocFile($namespace, $docs) {
  $filename    = TERMINUS_ROOT . '/docs/'
    . str_replace(array('Terminus\\', '\\'), array('', '/'), $namespace)
    . '.md';
  $rendered_doc = Utils\twigRender(
    'doc.twig',
    $docs,
    array('namespace' => $namespace)
  );
  file_put_contents($filename, $rendered_doc);
  return true;
}

$library_files   = getFiles(TERMINUS_ROOT . '/php/Terminus/Models');
$tokenized_files = array();
foreach ($library_files as $filename) {
  $namespace                   = str_replace(
    array(TERMINUS_ROOT . '/php/', '.php', '/'),
    array('', '', '\\'),
    $filename
  );
  $tokenized_files[$namespace] = getTokens($filename);
}
$tokenized_files['Terminus\\Auth'] = getTokens(TERMINUS_ROOT . '/php/Terminus/Auth.php');

$file_functions = array();
foreach ($tokenized_files as $namespace => $tokens) {
  $file_functions[$namespace] = findTokenPatterns($tokens);
}

$documentation = array();
foreach ($file_functions as $namespace => $functions) {
  if (!empty($functions)) {
    $documentation[$namespace] = array();
    foreach ($functions as $name => $function) {
      $documentation[$namespace][$name] = parseDocs($function['T_DOC_COMMENT']);
    }
  }
}

foreach ($documentation as $namespace => $data) {
  writeDocFile($namespace, $data);
}
