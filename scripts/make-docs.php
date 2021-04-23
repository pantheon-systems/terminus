<?php

define('TERMINUS_ROOT', dirname(__DIR__));
define('TERMINUS_DOC_ROOT', TERMINUS_ROOT . '/docs');
require(TERMINUS_ROOT . '/php/Terminus/Helpers/TerminusHelper.php');
require(TERMINUS_ROOT . '/php/Terminus/Helpers/TemplateHelper.php');
require(TERMINUS_ROOT . '/vendor/autoload.php');

/**
 * Recursively ensures that the location to write a file to exists
 *
 * @param string $filename Name of the file to be created
 * @return void
 */
function ensureDestinationExists($filename)
{
    $dir = dirname($filename);
    if (!file_exists($dir)) {
        ensureDestinationExists($dir);
        mkdir($dir);
    }
}

/**
 * Accepts an array representing a tokenized PHP file and sifts out all
 * sets of tokens meeting a specific pattern
 *
 * @param array $tokens  A tokenized PHP file
 * @param array $pattern Token name pattern to search out
 * @return array
 */
function findTokenPatterns(
    array $tokens,
    array $pattern = array('T_DOC_COMMENT', 'T_PUBLIC', 'T_FUNCTION', 'T_STRING')
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
 * @param string $dir_name Name of the directory from which to extract file names
 * @return string[] $file_list
 */
function getFiles($dir_name)
{
    $dir_files = scandir($dir_name);
    $file_list = array();
    foreach ($dir_files as $file_name) {
        $file = "$dir_name/$file_name";
        if (is_file($file)) {
             $file_list[] = $file;
        } elseif (($file_name[0] != '.') && is_dir($file)) {
            $file_list = array_merge(getFiles($file), $file_list);
        }
    }
    return $file_list;
}

/**
 * Uses PHP Tokenizer to extract documentation from the PHP files
 *
 * @param string $file_name Name of the file to read and tokenize
 * @return array
 */
function getTokens($file_name)
{
    $file_contents = file_get_contents($file_name);
    $tokens        = token_get_all($file_contents);
    return $tokens;
}

/**
 * Parses PHP internal documentation into chunks
 *
 * @param string $doc_string The raw doc string from the PHP file
 * @return array
 */
function parseDocs($doc_string)
{
    $exploded_docs = explode("\n", $doc_string);
    $lines         = array();
    foreach ($exploded_docs as $doc_line) {
        $line = trim(str_replace(array('/**', '*/', '*'), '', trim($doc_line)));
        if (!empty($line)) {
            $lines[] = $line;
        }
    }
    $parsed_doc = ['description' => [], 'param' => [], 'return' => [], 'throws' => [],];
    $current    = 'description';
    do {
        $line = array_shift($lines);
        if ($line[0] == '@') {
            $breakdown = explode(' ', $line);
            $current   = substr($breakdown[0], 1);
            unset($breakdown[0]);
            if ($current == 'param' || $current == 'return') {
                if (substr($breakdown[1], 0, 1) != '[') {
                    $breakdown[1] = '[' . $breakdown[1] . ']';
                }
            }
            $line = implode(' ', $breakdown);
        } elseif ($current != 'description') {
            $line = "-$line";
        }
        $parsed_doc[$current][] = $line;
    } while (!empty($lines));
    return $parsed_doc;
}

/**
 * Writes documentation to file
 *
 * @param string   $namespace Namespace to which the doc file will pertain
 * @param string[] $docs      Documentation to add to file
 * @return bool    True if write was successful
 */
function writeDocFile($namespace, $docs)
{
    $template_helper = new \Terminus\Helpers\TemplateHelper(['command' => null]);

    $filename    = TERMINUS_DOC_ROOT . '/'
    . str_replace(array('Terminus\\', '\\'), array('', '/'), $namespace)
    . '.md';
    $rendered_doc = $template_helper->render(
        [
        'template_name' => 'doc.twig',
        'data'          => $docs,
        'options'       => ['namespace' => $namespace,],
        ]
    );
    ensureDestinationExists($filename);
    file_put_contents($filename, $rendered_doc);
    return true;
}

$library_files   = array_merge(
    getFiles(TERMINUS_ROOT . '/php/Terminus/Models'),
    getFiles(TERMINUS_ROOT . '/php/Terminus/Outputters'),
    getFiles(TERMINUS_ROOT . '/php/Terminus/Helpers')
);
$tokenized_files = array();
foreach ($library_files as $filename) {
    $namespace                   = str_replace(
        array(TERMINUS_ROOT . '/php/', '.php', '/'),
        array('', '', '\\'),
        $filename
    );
    $tokenized_files[$namespace] = getTokens($filename);
}

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
