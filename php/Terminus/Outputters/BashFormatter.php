<?php
/**
 * @file
 * Contains Terminus\Outputters\BashFormatter
 */


namespace Terminus\Outputters;


/**
 * Class BashFormatter
 * @package Terminus\Outputters
 */
class BashFormatter implements OutputFormatterInterface {
  const FIELD_SEPARATOR = ' ';
  const ROW_SEPARATOR = "\n";
  const VALUE_SEPARATOR = ',';

  /**
   * Formats a single scalar value.
   *
   * @param mixed $value
   *  The scalar value to format
   * @param [string] $label Key for label to look up
   * @return string
   */
  public function formatValue($value, $label = '') {
    $value = BashFormatter::flattenValue($value);
    return $value . BashFormatter::ROW_SEPARATOR;
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function formatRecord($record) {
    $out = '';
    foreach ((array)$record as $key => $value) {
      $value = BashFormatter::flattenValue($value);
      $out .= $key . BashFormatter::FIELD_SEPARATOR . $value . BashFormatter::ROW_SEPARATOR;
    }
    return $out;
  }

  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function formatValueList($values) {
    $out = '';
    foreach ($values as $value) {
      $out .= $this->formatValue($value);
    }
    return $out;
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function formatRecordList($records) {
    $out = '';
    foreach ($records as $record) {
      foreach ((array)$record as $value) {
        $out .= BashFormatter::flattenValue($value);
        $out .= BashFormatter::FIELD_SEPARATOR;
      }
      // Remove the trailing separator.
      $out = substr($out, 0, strlen($out)-(strlen(BashFormatter::FIELD_SEPARATOR)));
      $out .= BashFormatter::ROW_SEPARATOR;
    }
    return $out;
  }

  /**
   * Format any kind of value as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function formatDump($object) {
    return print_r($object, true);
  }

  /**
   * Flatten a value for display
   * @param $value
   */
  private static function flattenValue($value) {
    if (is_array($value) || is_object($value)) {
      $value = join(BashFormatter::VALUE_SEPARATOR, (array)$value);
    }
    return $value;
  }
}
