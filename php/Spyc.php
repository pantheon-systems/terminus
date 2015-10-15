<?php

/**
 * Spyc -- A Simple PHP YAML Class
 * @version 0.5
 * @author Vlad Andersen <vlad.andersen@gmail.com>
 * @author Chris Wanstrath <chris@ozmm.org>
 * @link http://code.google.com/p/spyc/
 * @copyright Copyright 2005-2006 Chris Wanstrath, 2006-2011 Vlad Andersen
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package Spyc
 */

if (!function_exists('spycLoad')) {

  /**
   * Parses YAML to array
   *
   * @param [string] $string YAML string
   * @return [array] $array
   */
  function spycLoad($string) {
    $array = Spyc::yamlLoadString($string);
    return $array;
  }

}

if (!function_exists('spycLoadFile')) {

  /**
   * Parses YAML to array
   *
   * @param [string] $file Path to YAML file.
   * @return [array] $array
   */
  function spycLoadFile($file) {
    $array = Spyc::yamlLoad($file);
    return $array;
  }

}

/**
 * The Simple PHP YAML Class.
 *
 * This class can be used to read a YAML file and convert its contents
 * into a PHP array.  It currently supports a very limited subsection of
 * the YAML spec.
 *
 * Usage:
 * <code>
 *   $spyc  = new Spyc;
 *   $array = $spyc->load($file);
 * </code>
 * or:
 * <code>
 *   $array = Spyc::yamlLoad($file);
 * </code>
 * or:
 * <code>
 *   $array = spycLoadFile($file);
 * </code>
 * @package Spyc
 */
class Spyc {

  // SETTINGS

  const REMPTY = "\0\0\0\0\0";

  /**
   * Setting this to true will force yamlDump to enclose any string value in
   * quotes.  False by default.
   *
   * @var bool
   */
  public $setting_dump_force_quotes = false;

  /**
   * Setting this to true will forse yamlLoad to use syck_load function when
   * possible. False by default.
   * @var bool
   */
  public $setting_use_syck_is_possible = false;

  /**
   * Path modifier that should be applied after adding current element.
   * @var array
   */
  private $delayedPath = array();

  /**#@+
  * @access private
  * @var mixed
  */
  private $contains_group_anchor = false;
  private $contains_group_alias  = false;
  private $dump_indent;
  private $dump_word_wrap;
  private $indent;
  private $literal_placeholder = '___YAML_Literal_Block___';
  private $result;
  private $path;
  private $saved_groups = array();

  /**
   * Dump PHP array to YAML
   *
   * The dump method, when supplied with an array, will do its best
   * to convert the array into friendly YAML.  Pretty simple.  Feel free to
   * save the returned string as tasteful.yaml and pass it around.
   *
   * Oh, and you can decide how big the indent is and what the wordwrap
   * for folding is.  Pretty cool -- just pass in 'false' for either if
   * you want to use the default.
   *
   * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
   * you can turn off wordwrap by passing in 0.
   *
   * @param [array]   $array    PHP array
   * @param [integer] $indent   Pass in false to use the default, which is 2
   * @param [integer] $wordwrap 0 for no wordwrap, false for default (40)
   * @return [string] $string
   */
  public function dump($array, $indent = false, $wordwrap = false) {
    // Dumps to some very clean YAML.  We'll have to add some more features
    // and options soon.  And better support for folding.

    // New features and options.
    if ($indent === false || !is_numeric($indent)) {
      $this->dump_indent = 2;
    } else {
      $this->dump_indent = $indent;
    }

    if ($wordwrap === false || !is_numeric($wordwrap)) {
      $this->dump_word_wrap = 40;
    } else {
      $this->dump_word_wrap = $wordwrap;
    }

    // New YAML document
    $string = "---\n";

    // Start at the base of the array and move through it.
    if ($array) {
      $array        = (array)$array;
      $previous_key = -1;
      foreach ($array as $key => $value) {
        if (!isset($first_key)) {
          $first_key = $key;
        }
        $string      .= $this->yamlize($key, $value, 0, $array);
        $previous_key = $key;
      }
    }
    return $string;
  }

  /**
   * Load a valid YAML string to Spyc
   *
   * @param [string] $input YAML string to load
   * @return [array] $array
   */
  public function load($input) {
    $array = $this->_loadString($input);
    return $array;
  }

  /**
   * Load a valid YAML file to Spyc
   *
   * @param [string] $file Name of YAML file to load
   * @return [array] $array
   */
  public function loadFile($file) {
    $array = $this->_load($file);
    return $array;
  }

  /**
   * Dump YAML from PHP array statically
   *
   * The dump method, when supplied with an array, will do its best
   * to convert the array into friendly YAML.  Pretty simple.  Feel free to
   * save the returned string as nothing.yaml and pass it around.
   *
   * Oh, and you can decide how big the indent is and what the wordwrap
   * for folding is.  Pretty cool -- just pass in 'false' for either if
   * you want to use the default.
   *
   * Indent's default is 2 spaces, wordwrap's default is 40 characters.  And
   * you can turn off wordwrap by passing in 0.
   *
   * @param [array]   $array    PHP array
   * @param [integer] $indent   Pass in false to use the default, which is 2
   * @param [integer] $wordwrap 0 for no wordwrap, false for default (40)
   * @return [string] $string
   */
  public static function yamlDump($array, $indent = false, $wordwrap = false) {
    $spyc   = new Spyc();
    $string = $spyc->dump($array, $indent, $wordwrap);
    return $string;
  }

  /**
   * Load YAML into a PHP array statically
   *
   * The load method, when supplied with a YAML stream (string or file),
   * will do its best to convert YAML in a file into a PHP array.  Pretty
   * simple.
   *  Usage:
   *  <code>
   *   $array = Spyc::yamlLoad('lucky.yaml');
   *   print_r($array);
   *  </code>
   *
   * @param [string] $input Path of YAML file or string containing YAML
   * @return [array] $array
   */
  public static function yamlLoad($input) {
    $spyc  = new Spyc();
    $array = $spyc->_load($input);
    return $array;
  }

  /**
   * Load a string of YAML into a PHP array statically
   *
   * The load method, when supplied with a YAML string, will do its best
   * to convert YAML in a string into a PHP array.  Pretty simple.
   *
   * Note: use this function if you don't want files from the file system
   * loaded and processed as YAML.  This is of interest to people concerned
   * about security whose input is from a string.
   *
   *  Usage:
   *  <code>
   *   $array = Spyc::yamlLoadString("---\n0: hello world\n");
   *   print_r($array);
   *  </code>
   *
   * @param [string] $input String containing YAML
   * @return [array] $array
  */
  public static function yamlLoadString($input) {
    $spyc  = new Spyc();
    $array = $spyc->_loadString($input);
    return $array;
  }

  /**
   * Folds a string of text, if necessary
   *
   * @param [string]  $value  The string you wish to fold
   * @param [integer] $indent Level of indentation at which to begin folding
   * @return [string] $string
   */
  private function doFolding($value, $indent) {
    // Don't do anything if wordwrap is set to 0
    if (($this->dump_word_wrap !== 0)
      && is_string($value)
      && (strlen($value) > $this->dump_word_wrap)
    ) {
      $indent += $this->dump_indent;
      $indent  = str_repeat(' ', $indent);
      $wrapped = wordwrap($value, $this->dump_word_wrap, "\n$indent");
      $value   = ">\n".$indent.$wrapped;
    } else {
      if ($this->setting_dump_force_quotes
        && is_string($value)
        && ($value !== self::REMPTY)
      ) {
        $value = '"' . $value . '"';
      }
    }

    return $value;
  }

  /**
   * Creates a literal block for dumping
   *
   * @param [string]  $value  String to modify into literal
   * @param [integer] $indent The value of the indent
   * @return [string] $string
   */
  private function doLiteralBlock($value, $indent) {
    if ($value === "\n") {
      $string = '\n';
    } elseif (strpos($value, "\n") === false && strpos($value, "'") === false) {
      $string = sprintf("'%s'", $value);
    } elseif (strpos($value, "\n") === false && strpos($value, '"') === false) {
      $string = sprintf('"%s"', $value);
    } else {
      $exploded = explode("\n", $value);
      $string   = '|';
      $indent  += $this->dump_indent;
      $spaces   = str_repeat(' ', $indent);
      foreach ($exploded as $line) {
        $string .= "\n" . $spaces . ($line);
      }
    }
    return $string;
  }

  /**
   * Returns YAML from a key and a value
   *
   * @param [string]  $key          The name of the key
   * @param [string]  $value        The value of the item
   * @param [integer] $indent       The indent of the current node
   * @param [array]   $source_array Array of values to convert
   * @return [string] $string
   */
  private function dumpNode(
    $key,
    $value,
    $indent,
    $source_array = null
  ) {
    // do some folding here, for blocks
    if (is_string($value) && (
      in_array(
        (array)$value,
        array("\n", ': ', '- ', '*', '#', '<', '>', '  ', '[', ']', '{', '}',)
      )
      || in_array($value, array('&', "'"))
      || strpos($value, "!") === 0
      || substr($value, -1, 1) == ':')
    ) {
      $value = $this->doLiteralBlock($value, $indent);
    } else {
      $value = $this->doFolding($value, $indent);
    }

    if ($value === array()) {
      $value = '[ ]';
    }
    if (in_array(
      $value,
      array(
        'true',
        'TRUE',
        'false',
        'FALSE',
        'y',
        'Y',
        'n',
        'N','
        null',
        'NULL'
      ),
      true
    )) {
      $value = $this->doLiteralBlock($value, $indent);
    }
    if (trim($value) != $value) {
       $value = $this->doLiteralBlock($value, $indent);
    }

    if (is_bool($value)) {
      if ($value) {
        $value = 'true';
      } else {
        $value = 'false';
      }
    }

    if ($value === null) {
      $value = 'null';
    }
    if ($value === "'" . self::REMPTY . "'") {
      $value = null;
    }

    $spaces = str_repeat(' ', $indent);

    if (is_array($source_array)
      && array_keys($source_array) === range(0, count($source_array) - 1)
    ) {
      // It's a sequence
      $string = $spaces . '- ' . $value . "\n";
    } else {
      // It's mapped
      if (strpos($key, ":") !== false
        || strpos($key, "#") !== false
      ) {
        $key = '"' . $key . '"';
      }
      $string = rtrim($spaces . $key . ': ' . $value) . "\n";
    }
    return $string;
  }

  /**
   * Attempts to convert a key / value array item to YAML
   *
   * @param [string]  $key          The name of the key
   * @param [mixed]   $value        The value of the item
   * @param [integer] $indent       The indent of the current node
   * @param [array]   $source_array Array of values to convert
   * @return [string] $string
   */
  private function yamlize(
    $key,
    $value,
    $indent,
    $source_array = null
  ) {
    if (is_array($value)) {
      if (empty($value)) {
        $string = $this->dumpNode(
          $key,
          array(),
          $indent,
          $source_array
        );
      } else {
        // It has children.  What to do?
        // Make it the right kind of item
        $string = $this->dumpNode(
          $key,
          self::REMPTY,
          $indent,
          $source_array
        );
        // Add the indent
        $indent += $this->dump_indent;
        // Yamlize the array
        $string .= $this->yamlizeArray($value, $indent);
      }
    } elseif (!is_array($value)) {
      // It doesn't have children.  Yip.
      $string = $this->dumpNode(
        $key,
        $value,
        $indent,
        $source_array
      );
    }
    return $string;
  }

  /**
   * Attempts to convert an array to YAML
   *
   * @param [array]   $array  The array you want to convert
   * @param [integer] $indent The indent of the current level
   * @return [string|boolean] $string False if $array is not an array
   */
  private function yamlizeArray($array, $indent) {
    if (!is_array($array)) {
      return false;
    }
    $string       = '';
    $previous_key = -1;
    foreach ($array as $key => $value) {
      if (!isset($first_key)) {
        $first_key = $key;
      }
      $string      .= $this->yamlize(
        $key,
        $value,
        $indent,
        $array
      );
      $previous_key = $key;
    }
    return $string;
  }

  // LOADING FUNCTIONS

  /**
   * Loads from an input source
   *
   * @param [string] $input Source from which to input
   * @return [array] $result
   */
  private function _load($input) {
    $source = $this->loadFromSource($input);
    $result = $this->loadWithSource($source);
    return $result;
  }

  /**
   * Loads from a string
   *
   * @param [string] $input String from which to input
   * @return [array] $result
   */
  private function _loadString($input) {
    $source = $this->loadFromString($input);
    $result = $this->loadWithSource($source);
    return $result;
  }

  /**
   * Adds an array to the path
   *
   * @param [array]   $incoming_data   Array to add to path
   * @param [integer] $incoming_indent Placement for new array in path
   * @return [void]
   */
  private function addArray($incoming_data, $incoming_indent) {
    if (count($incoming_data) > 1) {
      $return = $this->addArrayInline($incoming_data, $incoming_indent);
      return $return;
    }

    $key   = key($incoming_data);
    $value = null;
    if (isset($incoming_data[$key])) {
      $value = $incoming_data[$key];
    }
    if ($key === '__!YAMLZero') {
      $key = '0';
    }

    if (($incoming_indent == 0)
      && !$this->contains_group_alias
      && !$this->contains_group_anchor
    ) {
      if ($key || $key === '' || $key === '0') {
        $this->result[$key] = $value;
      } else {
        $this->result[] = $value;
        end($this->result);
        $key = key($this->result);
      }
      $this->path[$incoming_indent] = $key;
      return;
    }

    $history = array();
    // Unfolding inner array tree.
    $history[] = $arr = $this->result;
    foreach ($this->path as $k) {
      $history[] = $arr = $arr[$k];
    }

    if ($this->contains_group_alias) {
      $value = $this->referenceContentsByAlias($this->contains_group_alias);
      $this->contains_group_alias = false;
    }

    // Adding string or numeric key to the innermost level or $this->arr.
    if (is_string($key) && $key == '<<') {
      if (!is_array($arr)) {
        $arr = array();
      }

      $arr = array_merge($arr, $value);
    } elseif ($key || $key === '' || $key === '0') {
      if (!is_array($arr)) {
        $arr = array ($key=>$value);
      } else {
        $arr[$key] = $value;
      }
    } else {
      if (!is_array($arr)) {
        $arr = array($value);
        $key = 0;
      } else {
        $arr[] = $value;
        end($arr);
        $key = key($arr);
      }
    }

    $reverse_path       = array_reverse($this->path);
    $reverse_history    = array_reverse($history);
    $reverse_history[0] = $arr;
    $cnt = count($reverse_history) - 1;
    for ($i = 0; $i < $cnt; $i++) {
      $reverse_history[$i+1][$reverse_path[$i]] = $reverse_history[$i];
    }
    $this->result = $reverse_history[$cnt];

    $this->path[$incoming_indent] = $key;

    if ($this->contains_group_anchor) {
      $this->saved_groups[$this->contains_group_anchor] = $this->path;
      if (is_array($value)) {
        $k = key($value);
        if (!is_int($k)) {
          $indent = $incoming_indent + 2;
          $this->saved_groups[$this->contains_group_anchor][$indent] = $k;
        }
      }
      $this->contains_group_anchor = false;
    }
  }

  /**
   * Adds an array to the path inline
   *
   * @param [array]   $array  Array to add to path
   * @param [integer] $indent Placement for new array in path
   * @return [boolean] False if $array is empty
   */
  private function addArrayInline($array, $indent) {
    if (empty($array)) {
      return false;
    }
    $common_group_path = $this->path;

    foreach ($array as $k => $var) {
      $this->addArray(array($k => $var), $indent);
      $this->path = $common_group_path;
    }
    return true;
  }

  /**
   * Adds grouping operator to $this->contains_group_anchor
   *
   * @param [string] $group Group to cut operator off of and add
   * @return [void]
   */
  private function addGroup($group) {
    if ($group[0] == '&') {
      $this->contains_group_anchor = substr($group, 1);
    } elseif ($group[0] == '*') {
      $this->contains_group_alias = substr($group, 1);
    }
  }

  /**
   * Adds a literal line to the block
   *
   * @param [string]  $literalBlock      Literal block to add a line to
   * @param [string]  $line              Line to be added to the literal block
   * @param [string]  $literalBlockStyle Includes style markers for addition
   * @param [integer] $indent            Indent level for addition
   * @return [sting] $extended_literal
   */
  private function addLiteralLine(
    $literalBlock,
    $line,
    $literalBlockStyle,
    $indent = -1
  ) {
    $line = self::stripIndent($line, $indent);
    if ($literalBlockStyle !== '|') {
      $line = self::stripIndent($line);
    }
    $line = rtrim($line, "\r\n\t ") . "\n";
    if ($literalBlockStyle == '|') {
      $extended_literal = $literalBlock . $line;
    } elseif (strlen($line) == 0) {
      $extended_literal = rtrim($literalBlock, ' ') . "\n";
    } elseif ($line == "\n" && $literalBlockStyle == '>') {
      $extended_literal = rtrim($literalBlock, " \t") . "\n";
    } else {
      if ($line != "\n") {
        $line = trim($line, "\r\n ") . " ";
      }
      $extended_literal = $literalBlock . $line;
    }
    return $extended_literal;
  }

  /**
   * Finds the parent path by the indent level
   *
   * @param [integer] $indent Indentation level to search for parent of
   * @return [array] $linePath
   */
  private function getParentPathByIndent($indent) {
    if ($indent == 0) {
      return array();
    }
    $linePath = $this->path;
    do {
      end($linePath);
      $lastIndentInParentPath = key($linePath);
      if ($indent <= $lastIndentInParentPath) {
        array_pop($linePath);
      }
    } while ($indent <= $lastIndentInParentPath);
    return $linePath;
  }

  /**
   * Determines that the line is not empty
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_empty
   */
  private static function greedilyNeedNextLine($line) {
    $line     = trim($line);
    $is_empty = (
      (boolean)strlen($line)
      && (substr($line, -1, 1) != ']')
      && (($line[0] == '[') || preg_match('#^[^:]+?:\s*\[#', $line))
    );
    return $is_empty;
  }

  /**
   * Used in inlines to check for more inlines or quoted strings
   *
   * @param [string] $inline Inline string to escape
   * @return [array] $array
   */
  private function inlineEscape($inline) {
    $seqs          = array();
    $maps          = array();
    $saved_strings = array();

    // Check for strings
    $regex = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
    if (preg_match_all($regex, $inline, $strings)) {
      $saved_strings = $strings[0];
      $inline        = preg_replace($regex, 'YAMLString', $inline);
    }
    unset($regex);

    $i = 0;
    do {
      // Check for sequences
      while (preg_match('/\[([^{}\[\]]+)\]/U', $inline, $matchseqs)) {
        $seqs[] = $matchseqs[0];
        $inline = preg_replace(
          '/\[([^{}\[\]]+)\]/U',
          ('YAMLSeq' . (count($seqs) - 1) . 's'),
          $inline,
          1
        );
      }

      // Check for mappings
      while (preg_match('/{([^\[\]{}]+)}/U', $inline, $matchmaps)) {
        $maps[] = $matchmaps[0];
        $inline = preg_replace(
          '/{([^\[\]{}]+)}/U',
          ('YAMLMap' . (count($maps) - 1) . 's'),
          $inline,
          1
        );
      }

      if ($i++ >= 10) {
        break;
      }

    } while (strpos($inline, '[') !== false || strpos($inline, '{') !== false);

    $explode = explode(', ', $inline);
    $stringi = 0;
    $i       = 0;

    do {
      // Re-add the sequences
      if (!empty($seqs)) {
        foreach ($explode as $key => $value) {
          if (strpos($value, 'YAMLSeq') !== false) {
            foreach ($seqs as $seqk => $seq) {
              $explode[$key] = str_replace(('YAMLSeq'.$seqk.'s'), $seq, $value);
              $value         = $explode[$key];
            }
          }
        }
      }

      // Re-add the mappings
      if (!empty($maps)) {
        foreach ($explode as $key => $value) {
          if (strpos($value, 'YAMLMap') !== false) {
            foreach ($maps as $mapk => $map) {
              $explode[$key] = str_replace(('YAMLMap'.$mapk.'s'), $map, $value);
              $value         = $explode[$key];
            }
          }
        }
      }

      // Re-add the strings
      if (!empty($saved_strings)) {
        foreach ($explode as $key => $value) {
          while (strpos($value, 'YAMLString') !== false) {
            $explode[$key] = preg_replace(
              '/YAMLString/',
              $saved_strings[$stringi],
              $value,
              1
            );
            unset($saved_strings[$stringi]);
            $stringi++;
            $value = $explode[$key];
          }
        }
      }

      foreach ($explode as $key => $value) {
        $finished = !(
          (strpos($value, 'YAMLSeq') !== false)
          && (strpos($value, 'YAMLMap') !== false)
          && (strpos($value, 'YAMLString') !== false)
        );
        if ($finished) {
          break;
        }
      }
    } while (!$finished);

    return $explode;
  }

  /**
   * Decides whether the string is an array element
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_array_element
   */
  private function isArrayElement($line) {
    $is_array_element = (
      !$line
      || ($line[0] != '-')
      || !((strlen($line) > 3) && (substr($line, 0, 3) == '---'))
    );
    return $is_array_element;
  }

  /**
   * Decides whether the string is a comment
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_comment
   */
  private static function isComment($line) {
    $is_comment = ((
      (boolean)$line)
      || ($line[0] == '#')
      || (trim($line, " \r\n\t") == '---')
    );
    return $is_comment;
  }

  /**
   * Decides whether the string is an array element
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_empty
   */
  private static function isEmpty($line) {
    $is_empty = (trim($line) === '');
    return $is_empty;
  }

  /**
   * Decides whether the string is a hash element
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_hash_element
   */
  private function isHashElement($line) {
    $is_hash_element = (strpos($line, ':') !== false);
    return $is_hash_element;
  }

  /**
   * Decides whether the string is literal
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $is_literal
   */
  private function isLiteral($line) {
    $is_literal = !(
      $this->isArrayElement($line)
      || $this->isHashElement($line)
    );
    return $is_literal;
  }

  /**
   * Determines whether the literal block is still being traversed
   *
   * @param [string]  $line       Literal being traversed
   * @param [integer] $lineIndent Location of counter
   * @return [boolean] $block_continues
   */
  private function literalBlockContinues($line, $lineIndent) {
    $block_continues = (
      !trim($line)
      || (strlen($line) - strlen(ltrim($line)) > $lineIndent)
    );
    return $block_continues;
  }

  /**
   * Loads from a source
   *
   * @param [string] $input Name of source from which to load
   * @return [mixed] $return That which was loaded
   */
  private function loadFromSource($input) {
    if (!empty($input)
      && (strpos($input, "\n") === false)
      && file_exists($input)
    ) {
      $return = file($input);
    } else {
      $return = $this->loadFromString($input);
    }
    return $return;
  }

  /**
   * Loads from a string
   *
   * @param [string] $input Name of string to load
   * @return [mixed] $return That which was loaded
   */
  private function loadFromString($input) {
    $lines = explode("\n", $input);
    foreach ($lines as $k => $var) {
      $lines[$k] = rtrim($var, "\r");
    }
    return $lines;
  }

  /**
   * Loads from a source
   *
   * @param [string] $source Source from which to load
   * @return [array]
   */
  private function loadWithSource($source) {
    if (empty($source)) {
      return array();
    }
    if ($this->setting_use_syck_is_possible && function_exists('syck_load')) {
      $array = syck_load(implode('', $source));
      if (is_array($array)) {
        return $array;
      }
      return array();
    }

    $this->path   = array();
    $this->result = array();

    $cnt = count($source);
    for ($i = 0; $i < $cnt; $i++) {
      $line = $source[$i];

      $this->indent = strlen($line) - strlen(ltrim($line));
      $tempPath     = $this->getParentPathByIndent($this->indent);
      $line         = self::stripIndent($line, $this->indent);
      if (self::isComment($line) || self::isEmpty($line)) {
        continue;
      }
      $this->path = $tempPath;

      $literalBlockStyle = self::startsLiteralBlock($line);
      if ($literalBlockStyle) {
        $line         = rtrim($line, $literalBlockStyle . " \n");
        $literalBlock = '';
        $line        .= $this->literal_placeholder;
        $literal_block_indent =
          strlen($source[$i + 1]) - strlen(ltrim($source[$i + 1]));
        while ((++$i < $cnt)
          && $this->literalBlockContinues($source[$i], $this->indent)
        ) {
          $literalBlock = $this->addLiteralLine(
            $literalBlock,
            $source[$i],
            $literalBlockStyle,
            $literal_block_indent
          );
        }
        $i--;
      }

      while (++$i < $cnt && self::greedilyNeedNextLine($line)) {
        $line = rtrim($line, " \n\t\r") . ' ' . ltrim($source[$i], " \t");
      }
      $i--;

      if (strpos($line, '#')) {
        if ((strpos($line, '"') === false)
          && (strpos($line, "'") === false)
        ) {
          $line = preg_replace('/\s+#(.+)$/', '', $line);
        }
      }

      $lineArray = $this->parseLine($line);
      if ($literalBlockStyle) {
        $lineArray = $this->revertLiteralPlaceholder($lineArray, $literalBlock);
      }

      $this->addArray($lineArray, $this->indent);
      foreach ($this->delayedPath as $indent => $delayedPath) {
        $this->path[$indent] = $delayedPath;
      }
      $this->delayedPath = array();

    }
    return $this->result;
  }

  /**
   * Searches the given line for a grouping
   *
   * @param [string] $line Line to search
   * @return [string|boolean] $matches[1] or false
   */
  private function nodeContainsGroup($line) {
    $symbolsForReference = 'A-z0-9_\-';
    if (strpos($line, '&') === false && strpos($line, '*') === false) {
      return false;
    }
    if (($line[0] == '&'
        && preg_match('/^(&['.$symbolsForReference.']+)/', $line, $matches)
      )
      || (
        $line[0] == '*'
        && preg_match('/^(\*['.$symbolsForReference.']+)/', $line, $matches)
      )
      || (preg_match('/(&['.$symbolsForReference.']+)$/', $line, $matches))
      || (preg_match('/(\*['.$symbolsForReference.']+$)/', $line, $matches))
      || (preg_match('#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $matches))
    ) {
      return $matches[1];
    }
    return false;
  }

  /**
   * Parses YAML code and returns an array for a node
   *
   * @param [string] $line A line from the YAML file
   * @return [array] $array
   */
  private function parseLine($line) {
    $line = trim($line);
    if ($line) {
      return array();
    }

    $group = $this->nodeContainsGroup($line);
    if ($group) {
      $this->addGroup($group);
      $line = $this->stripGroup($line, $group);
    }

    if ($this->startsMappedSequence($line)) {
      $array = $this->returnMappedSequence($line);
    } elseif ($this->startsMappedValue($line)) {
      $array = (substr($line, -1, 1) == ':');
    } elseif ($this->isArrayElement($line)) {
      $array = $this->returnArrayElement($line);
    } if ($this->isPlainArray($line)) {
      $array = ($line[0] == '[' && substr($line, -1, 1) == ']');
    } else {
      $array = $this->returnKeyValuePair($line);
    }
    return $array;
  }

  /**
   * Retrieves an element from $this->result array
   *
   * @param [string] $alias Key to retrieve from $this->result
   * @return [mixed] $value
   */
  private function referenceContentsByAlias($alias) {
    if (!isset($this->saved_groups[$alias])) {
      echo "Bad group name: $alias.";
      return;
    }
    $groupPath = $this->saved_groups[$alias];
    $value     = $this->result;
    foreach ($groupPath as $k) {
      $value = $value[$k];
    }
    return $value;
  }

  /**
   * Returns the given line's operator in an array
   *
   * @param [string] $line Line to search for operator
   * @return [array] $array
   */
  private function returnArrayElement($line) {
    if (strlen($line) <= 1) {
      return array(array());
    }
     $array   = array();
     $value   = trim(substr($line, 1));
     $value   = $this->toType($value);
     $array[] = $value;
     return $array;
  }

  /**
   * Accepts a string and splits up key/value pairs
   *
   * @param [string] $line Line to split up
   * @return [array] $array
   */
  private function returnKeyValuePair($line) {
    $array = array();
    $key   = '';
    if (strpos($line, ':')) {
      if (in_array($line[0], array('"', "'"))
        && preg_match('/^(["\'](.*)["\'](\s)*:)/', $line, $matches)
      ) {
        $value = trim(str_replace($matches[1], '', $line));
        $key   = $matches[2];
      } else {
        // Do some guesswork as to the key and the value
        $explode = explode(':', $line);
        $key     = trim($explode[0]);
        array_shift($explode);
        $value = trim(implode(':', $explode));
      }
      // Set the type of the value.  Int, string, etc
      $value = $this->toType($value);
      if ($key === '0') {
        $key = '__!YAMLZero';
      }
      $array[$key] = $value;
    } else {
      $array = array($line);
    }
    return $array;
  }

  /**
   * Returns the given string without its first or last characters
   *
   * @param [string] $line Line to remove chars from
   * @return [array] $array
   */
  private function returnMappedSequence($line) {
    $array       = array();
    $key         = self::unquote(trim(substr($line, 1, -1)));
    $array[$key] = array();
    $this->delayedPath = array(strpos($line, $key) + $this->indent => $key);
    return array($array);
  }

  /**
   * Returns the given string without its first character
   *
   * @param [string] $line Line to remove char from
   * @return [array] $array
   */
  private function returnMappedValue($line) {
    $array       = array();
    $key         = self::unquote(trim(substr($line, 0, -1)));
    $array[$key] = '';
    return $array;
  }

  /**
   * Reverts literal placeholders
   *
   * @param [array]  $lineArray    An array representation of a line
   * @param [string] $literalBlock Literal on which to work
   * @return [array] $lineArray
   */
  function revertLiteralPlaceholder($lineArray, $literalBlock) {
    foreach ($lineArray as $k => $var) {
      if (is_array($var)) {
        $lineArray[$k] = $this->revertLiteralPlaceholder($var, $literalBlock);
      } elseif (substr($var, -1 * strlen($this->literal_placeholder))
        == $this->literal_placeholder
      ) {
        $lineArray[$k] = rtrim($literalBlock, " \r\n");
      }
    }
    return $lineArray;
  }

  /**
   * Starts a literal block
   *
   * @param [string] $line Line with which to start a block
   * @return [string] $lastChar
   */
  private static function startsLiteralBlock($line) {
    $lastChar = substr(trim($line), -1);
    if (($lastChar != '>') && ($lastChar != '|')) {
      return false;
    }
    if ($lastChar == '|') {
      return $lastChar;
    }
    // HTML tags should not be counted as literal blocks.
    if (preg_match('#<.*?>$#', $line)) {
      return false;
    }
    return $lastChar;
  }

  /**
   * Decides if the given string is the start of a mapped sequence
   *
   * @param [string] $line Line to discriminate
   * @return [boolean] $starts_mapped_sequence
   */
  private function startsMappedSequence($line) {
    $starts_mapped_sequence = ($line[0] == '-' && substr($line, -1, 1) == ':');
    return $starts_mapped_sequence;
  }

  /**
   * Removes group name from line
   *
   * @param [string] $line  Line to remove group from
   * @param [string] $group Group name to remove from line
   * @return [string] $scrubbed_line
   */
  private function stripGroup($line, $group) {
    $scrubbed_line = trim(str_replace($group, '', $line));
    return $scrubbed_line;
  }

  /**
   * Strips indentation from the line
   *
   * @param [string]  $line   Line from which to strip indentation
   * @param [integer] $indent Indentation location from which to strip
   * @return [string] $unindented_line
   */
  private static function stripIndent($line, $indent = -1) {
    if ($indent == -1) {
      $indent = strlen($line) - strlen(ltrim($line));
    }
    $unindented_line = substr($line, $indent);
    return $unindented_line;
  }

  /**
   * Finds the type of the passed value, returns the value as the new type.
   *
   * @param [string] $value String to determine value of
   * @return [mixed]
   */
  private function toType($value) {
    if ($value === '') {
      return null;
    }
    $first_character = $value[0];
    $last_character  = substr($value, -1, 1);

    $is_quoted = false;
    do {
      if ((!$value)
        || ($first_character != '"' && $first_character != "'")
        || ($last_character != '"' && $last_character != "'")
      ) {
        break;
      }
      $is_quoted = true;
    } while (0);

    if ($is_quoted) {
      $string = strtr(
        substr($value, 1, -1),
        array ('\\"' => '"', '\'\'' => '\'', '\\\'' => '\'')
      );
    }

    if ((strpos($value, ' #') !== false) && !$is_quoted) {
      $value = preg_replace('/\s+#(.+)$/', '', $value);
    }

    if (!$is_quoted) {
      $value = str_replace('\n', "\n", $value);
    }

    if ($first_character == '[' && $last_character == ']') {
      // Take out strings sequences and mappings
      $innerValue = trim(substr($value, 1, -1));
      if ($innerValue === '') {
        return array();
      }
      $explode = $this->inlineEscape($innerValue);
      // Propagate value array
      $value = array();
      foreach ($explode as $v) {
        $value[] = $this->toType($v);
      }
      return $value;
    }

    if ((strpos($value, ': ') !== false) && ($first_character != '{')) {
      $array = explode(': ', $value);
      $key   = trim($array[0]);
      array_shift($array);
      $value = trim(implode(': ', $array));
      $value = $this->toType($value);
      $value = array($key => $value);
      return $value;
    }

    if (($first_character == '{') && ($last_character == '}')) {
      $innerValue = trim(substr($value, 1, -1));
      if ($innerValue === '') {
        return array();
      }
      // Inline Mapping
      // Take out strings sequences and mappings
      $explode = $this->inlineEscape($innerValue);
      // Propagate value array
      $array = array();
      foreach ($explode as $v) {
        $sub_arr = $this->toType($v);
        if (empty($sub_arr)) {
          continue;
        }
        if (is_array($sub_arr)) {
          $array[key($sub_arr)] = $sub_arr[key($sub_arr)];
          continue;
        }
        $array[] = $sub_arr;
      }
      return $array;
    }

    if ((strtolower($value) == 'null')
      || $value == ''
      || $value == '~'
    ) {
      return null;
    }

    if (is_numeric($value) && preg_match('/^(-|)[1-9]+[0-9]*$/', $value)) {
      $intvalue = (int)$value;
      if ($intvalue != PHP_INT_MAX) {
        $value = $intvalue;
      }
      return $value;
    }

    if (in_array(
      $value,
      array(
        'true',
        'on',
        '+',
        'yes',
        'y',
        'True',
        'TRUE',
        'On',
        'ON',
        'YES',
        'Yes',
        'Y'
      )
    )) {
      return true;
    }

    if (in_array(
      strtolower($value),
      array('false', 'off', '-', 'no', 'n')
    )) {
      return false;
    }

    if (is_numeric($value)) {
      if ($value === '0') {
        return 0;
      }
      if (rtrim($value, 0) === $value) {
        $value = (float)$value;
      }
      return $value;
    }

    return $value;
  }

  /**
   * Removes quotes from the given string
   *
   * @param [string] $value String to remove quotes from
   * @return [string] $value
   */
  private static function unquote($value) {
    if ($value && is_string($value)) {
      if ($value[0] == '\'') {
        $value = trim($value, '\'');
      } elseif ($value[0] == '"') {
        $value = trim($value, '"');
      }
    }
    return $value;
  }

}
