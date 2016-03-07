<?php

namespace Terminus;

class SynopsisParser {

  /**
   * Parses a command from documentation and command input
   *
   * @param string $synopsis Command synopsis from the inline docs
   * @return array List of parameters
   */
  static function parse($synopsis) {
    $tokens = array_filter(preg_split('/[\s\t]+/', $synopsis));

    $params = [];
    foreach ($tokens as $token) {
      $param = self::classifyToken($token);

      // Some types of parameters shouldn't be mandatory
      if (isset($param['optional']) && !$param['optional']) {
        if (in_array($param['type'], ['flag', 'assoc',])
          && $param['value']['optional']
        ) {
          $param['type'] = 'unknown';
        }
      }

      $param['token'] = $token;
      $params[]       = $param;
    }

    return $params;
  }

  /**
   * Decides type of token and sanitizes it appropriately.
   *
   * @param string $token Token to check for brackets
   * @return array $param Elements as follows:
   *         [string]  type      Gives token type (e.g. generic, flag, etc)
   *         [string]  name      Regex'd out name of token
   *         [boolean] optional  True if param is optional
   *         [boolean] repeating True if param is repeating
   */
  private static function classifyToken($token) {
    $param = [];

    list($param['optional'], $token)  = self::isOptional($token);
    list($param['repeating'], $token) = self::isRepeating($token);

    $p_name  = '([a-z-_]+)';
    $p_value = '([a-zA-Z-|]+)';

    // TODO: make this more flexible so that it doesn't need to be <field>
    if (preg_match("/^--<(\w+)>=<value>$/", $token, $matches)) {
      $param['type'] = 'generic';
      $param['name'] = $matches[1];
    } elseif (preg_match("/^<($p_value)>$/", $token, $matches)) {
      $param['type'] = 'positional';
      $param['name'] = $matches[1];
    } elseif (preg_match("/^--(?:\\[no-\\])?$p_name/", $token, $matches)) {
      $param['name'] = $matches[1];

      $value = substr($token, strlen($matches[0]));

      if (strlen($matches[0]) === strlen($token)) {
        $param['type'] = 'flag';
      } else {
        $param['type'] = 'assoc';

        list($param['value']['optional'], $value) = self::isOptional($value);

        if (preg_match("/^=<$p_value>$/", $value, $matches)) {
          $param['value']['name'] = $matches[1];
        } else {
          $param = ['type' => 'unknown',];
        }
      }
    } else {
      $param['type'] = 'unknown';
    }

    return $param;
  }

  /**
   * An optional parameter is surrounded by square brackets.
   *
   * @param string $token Token to check for brackets
   * @return array $array Elements as follows:
   *         [boolean] True if optional, false if not
   *         [string]  $token if optional, without brackets if not
   */
  private static function isOptional($token) {
    if ((substr($token, 0, 1) == '[') && (substr($token, -1) == ']')) {
      $array = [true, substr($token, 1, -1),];
    } else {
      $array = [false, $token,];
    }
    return $array;
  }

  /**
   * A repeating parameter is followed by an ellipsis.
   *
   * @param string $token Token to check for ellipses
   * @return array $array Elements as follows:
   *         [boolean] True if repeating, false if not
   *         [string]  $token if not repeating, without ellipses if not
   */
  private static function isRepeating($token) {
    if (substr($token, -3) == '...') {
      $array = [true, substr($token, 0, -3),];
    } else {
      $array = [false, $token,];
    }
    return $array;
  }

}
