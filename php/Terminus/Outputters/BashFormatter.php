<?php

/**
 * @file Contains Terminus\Outputters\BashFormatter
 */

namespace Terminus\Outputters;

/**
 * Class BashFormatter
 * @package Terminus\Outputters
 */
class BashFormatter implements OutputFormatterInterface {
  const FIELD_SEPARATOR = ' ';
  const ROW_SEPARATOR   = "\n";
  const VALUE_SEPARATOR = ',';

  /**
   * Formats any kind of value as a raw dump
   *
   * @param [mixed] $object An object to dump via print_r
   * @return [string] $printout
   */
  public function formatDump($object) {
    $printout = print_r($object, true);
    return $printout;
  }

  /**
   * Format a single record or object
   *
   * @param [array|object] $record       A key/value array or object
   * @param [array]        $human_labels A key/value array mapping the keys in
   *   the record to human labels
   * @return [string] $out
   */
  public function formatRecord($record, $human_labels = array()) {
    $out = '';
    foreach ((array)$record as $key => $value) {
      $value = BashFormatter::flattenValue($value);
      $out  .= $key . BashFormatter::FIELD_SEPARATOR . $value
        . BashFormatter::ROW_SEPARATOR;
    }
    return $out;
  }

  /**
   * Format a list of records of the same type.
   *
   * @param [array] $records      A list of arrays or objects.
   * @param [array] $human_labels An array mapping record keys to human names
   * @return [string] $table
   */
  public function formatRecordList($records, $human_labels = array()) {
    $out = '';
    foreach ($records as $record) {
      foreach ((array)$record as $value) {
        $out .= BashFormatter::flattenValue($value);
        $out .= BashFormatter::FIELD_SEPARATOR;
      }
      // Remove the trailing separator.
      $out  = substr(
        $out,
        0,
        (strlen($out)-(strlen(BashFormatter::FIELD_SEPARATOR)))
      );
      $out .= BashFormatter::ROW_SEPARATOR;
    }
    return $out;
  }

  /**
   * Formats a single scalar value with an optional human label.
   *
   * @param [mixed]  $value       A scalar value to format
   * @param [string] $human_label A human readable label for that value
   * @return [string] $formatted_value
   */
  public function formatValue($value, $human_label = '') {
    $value = BashFormatter::flattenValue($value);
      . BashFormatter::ROW_SEPARATOR;
    return $value;
  }

  /**
   * Format a list of scalar values
   *
   * @param [array]  $values      The values to format
   * @param [string] $human_label A human name for the entire list. If each
   *   value needs a separate label, then formatRecord should be used.
   * @return [string] $out
   */
  public function formatValueList($values, $human_label = '') {
    $out = '';
    foreach ($values as $value) {
      $out .= $this->formatValue($value);
    }
    return $out;
  }

  /**
   * Flatten a value for display
   *
   * @param [mixed] $value Value to stringify
   * @return [string] $value or $output
   */
  private static function flattenValue($value) {
    if (is_scalar($value)) {
      return $value;
    }
    $value  = (array)$value;
    $output = array();

    foreach ($value as $key => $val) {
      $output[] = $key . ': ' . BashFormatter::flattenValue($val);
    }
    $output = '(' . implode(BashFormatter::VALUE_SEPARATOR, $output) . ')';
    return $output;
  }

}
