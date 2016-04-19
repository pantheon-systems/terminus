<?php

namespace Terminus;

/**
 * Checks if the list of parameters matches the specification defined in the
 * synopsis.
 */
class SynopsisValidator {
  private $spec = array();

  /**
   * Object constructor. Puts synopsis parsing into spec property
   *
   * @param string $synopsis Synopsis from command's internal documentation
   */
  public function __construct($synopsis) {
    $this->spec = SynopsisParser::parse($synopsis);
  }

  /**
   * Determines unknown positionals
   *
   * @return array
   */
  public function getUnknown() {
    $array = array_column(
      $this->querySpec(array('type' => 'unknown')),
      'token'
    );
    return $array;
  }

  /**
   * Determines whether the command has already had its fill of positional args
   *
   * @param array $args The arguments to count against max args
   * @return bool
   */
  public function enoughPositionals($args) {
    $positional = $this->querySpec(
      array(
        'type'     => 'positional',
        'optional' => false,
      )
    );

    $enough_positionals = count($args) >= count($positional);
    return $enough_positionals;
  }

  /**
   * Returns invalid positionals, if any. False if not.
   *
   * @param array $args The arguments to evaluate for invalid positionals
   * @return string|bool Returns the first invalid positional or false
   */
  public function invalidPositionals($args) {
    $positionals = $this->querySpec(array('type' => 'positional'));
    $args_count  = count($args);

    for ($i = 0; $i < $args_count; $i++) {
      if (!isset($positionals[$i]['token'])) {
        continue;
      }
      $token = preg_replace(
        '#\[?\<([a-zA-Z].*)\>\]?.*#s',
        '$1',
        $positionals[$i]['token']
      );
      if (!strpos(trim($token), '|')) {
        // We exit here because this commands is accepting free arguments.
        return false;
      }
      $regex = "#^($token)$#s";

      if (!preg_match($regex, $args[$i])) {
        return $args[$i];
      }
    }
    return false;
  }

  /**
   * Returns unknown associated arguments (flags and params)
   *
   * @param array $assoc_args Params and flags to evaluate for unknowns
   * @return array
   */
  public function unknownAssoc($assoc_args) {
    $generic = $this->querySpec(array('type' => 'generic'));

    if (count($generic)) {
      return array();
    }

    $known_assoc = array();

    foreach ($this->spec as $param) {
      if (in_array($param['type'], array('assoc', 'flag'))) {
        $known_assoc[] = $param['name'];
      }
    }

    $unknowns = array_diff(array_keys($assoc_args), $known_assoc);
    return $unknowns;
  }

  /**
   * Returns unknown positional arguments
   *
   * @param array $args Positional args to evaluate for unknowns
   * @return array
   */
  public function unknownPositionals($args) {
    $positional_repeating = $this->querySpec(
      array(
        'type' => 'positional',
        'repeating' => true,
      )
    );

    if (!empty($positional_repeating)) {
      return array();
    }

    $positional = $this->querySpec(
      array(
        'type' => 'positional',
        'repeating' => false,
      )
    );

    $unknowns = array_slice($args, count($positional));
    return $unknowns;
  }

  /**
   * Checks that all required keys are present and that they have values.
   *
   * @param array $assoc_args Params and flags to evaluate for unknowns
   * @return array $feedback Elements as follows:
   *         [array] errors   Errors relating to any invalid associated args
   *         [array] to_unset The invalid arguments
   */
  public function validateAssoc($assoc_args) {
    $assoc_spec = $this->querySpec(array('type' => 'assoc'));

    $errors = array(
      'fatal'   => array(),
      'warning' => array()
    );

    $to_unset = array();

    foreach ($assoc_spec as $param) {
      $key = $param['name'];

      if (!isset($assoc_args[$key])) {
        if (!$param['optional']) {
          $errors['fatal'][] = "missing --$key parameter";
        }
      } else {
        if (($assoc_args[$key] === true) && !$param['value']['optional']) {
          $error_type = 'warning';
          if (!$param['optional']) {
            $error_type = 'fatal';
          }
          $errors[ $error_type ][] = "--$key parameter needs a value";

          $to_unset[] = $key;
        }
      }
    }

    $feedback = array($errors, $to_unset);
    return $feedback;
  }

  /**
   * Filters a list of associative arrays, based on a set of key => value
   * arguments.
   *
   * @param array  $args     An array of key => value arguments to match
   *                             against
   * @param string $operator AND, OR, or NOT
   * @return array List filtered by operator
   */
  private function querySpec($args, $operator = 'AND') {
    $operator = strtoupper($operator);
    $count    = count($args);
    $filtered = array();

    foreach ($this->spec as $key => $to_match) {
      $matched = 0;
      foreach ($args as $m_key => $m_value) {
        if (array_key_exists($m_key, $to_match)
          && ($m_value == $to_match[$m_key])
        ) {
          $matched++;
        }
      }

      if ((($operator == 'AND') && ($matched == $count))
        || (($operator == 'OR') && ($matched > 0))
        || (($operator == 'NOT') && ($matched == 0))
      ) {
        $filtered[$key] = $to_match;
      }
    }

    return $filtered;
  }

}
