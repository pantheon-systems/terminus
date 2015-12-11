<?php

define('ROOT_DIR', dirname(__DIR__));

/**
 * Accepts the an array of tokenized PHP files and sifts out the internal documentation
 *
 * @param [array] $tokenized_files Tokenized PHP files
 * @return [array] $documentation
 */
function extractDocumentation($tokenized_files) {
  $documentation = array();
  foreach ($tokenized_files as $name => $tokens) {
    $documentation[$name] = array();
    while (!empty($tokens)) {
      $token = array_shift($tokens);
      if (is_array($token) && (token_name($token[0]) == 'T_DOC_COMMENT')) {
        $documentation[$name] = $token[1];
      }
    }
  }
}

/**
 * Retrieves all file names from a directory
 *
 * @param $dir_name Name of the directory from which to extract file names
 * @return [array <String>] $file_list
 */
function getFiles($dir_name) {
  $file_list = array_filter(
    scandir($dir_name),
    function($value) {
      $is_valid = ($value[0] != '.');
      return $is_valid;
    }
  );
  array_walk(
    $file_list,
    function (&$file_name) use ($dir_name, &$file_list) {
      $file_name = "$dir_name/$file_name";
      if (is_dir($file_name)) {
        $file_list = array_merge(getFiles($file_name), $file_list);
      }
    }
  );
  $file_list = array_filter(
    $file_list,
    function ($file_name) {
      $is_file = !is_dir($file_name);
      return $is_file;
    }
  );
  return $file_list;
}

/**
 * Uses PHP Tokenizer to extract documentation from the PHP files
 *
 * @param [string] $file_name Name of the file to read and tokenize
 * @return [array] $tokens
 */
function gleanTokens($file_names) {
  $tokens = array();
  foreach ($file_names as $file_name) {
    $file_contents      = file_get_contents($file_name);
    $tokens[$file_name] = token_get_all($file_contents);
  }
  return $tokens;
}

$library_files   = getFiles(ROOT_DIR . '/php/Terminus/Models');
$tokenized_files = gleanTokens($library_files);
$documentation   = extractDocumentation($tokenized_files);
echo "Finished!\n";
