<?php

/**
 * @file Contains Terminus\Outputters\PrettyFormatter
 */

namespace Terminus\Outputters;

class PrettyFormatter implements OutputFormatterInterface {

  /**
   * Formats any kind of value as a raw dump
   *
   * @param mixed $object An object to dump via print_r
   * @return string
   */
  public function formatDump($object) {
    $printout = print_r($object, true);
    return $printout;
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record       A key/value array or object
   * @param array        $human_labels A key/value array mapping the keys in
   *   the record to human labels
   * @return string
   */
  public function formatRecord($record, array $human_labels = array()) {
    // No output for empty records. This should be handled by the logger with
    // a friendly message.
    if (empty($record)) {
      return '';
    }

    // Normalize the keys
    $rows = array();

    // Get the headers
    $record = (array)$record;
    foreach ((array)$record as $key => $value) {
      $label  = self::getHumanLabel($key, $human_labels);
      $rows[] = array($label, $value);
    }

    $table = $this->formatTable($rows, array('Key', 'Value'));
    return $table;
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records      A list of arrays or objects.
   * @param array $human_labels An array mapping record keys to human names
   * @return string
   */
  public function formatRecordList(array $records, array $human_labels = array()) {
     // No output for empty records. This should be handled by the logger with
     // a friendly message.
    if (empty($records)) {
      return '';
    }

    // TODO: Implement formatRecordList() method.
    // Normalize the keys
    $keys   = array();
    $header = array();
    // Get all of the keys from all of the records.
    foreach ($records as $record) {
      foreach ($record as $key => $value) {
        if (!in_array($key, $keys)) {
          $keys[] = $key;
        }
      }
    }
    // Add missing values
    foreach ($records as $i => $record) {
      $new    = array();
      $record = (array)$record;
      foreach ($keys as $key) {
        $new[$key] = '';
        if (isset($record[$key])) {
          $new[$key] = PrettyFormatter::flattenValue(
            $record[$key],
            $human_labels
          );
        }
      }
      $records[$i] = $new;
    }
    // Get the headers
    foreach ($keys as $key) {
      $header[] = self::getHumanLabel($key, $human_labels);
    }

    $table = $this->formatTable($records, $header);
    return $table;
  }

  /**
   * Formats a single scalar value with an optional human label.
   *
   * @param mixed  $value       A scalar value to format
   * @param string $human_label A human readable label for that value
   * @return string
   */
  public function formatValue($value, $human_label = '') {
    $formatted_value = PrettyFormatter::flattenValue($value) . PHP_EOL;
    if (!empty($human_label)) {
      $formatted_value = "$human_label: $formatted_value";
    }
    return $formatted_value;
  }

  /**
   * Format a list of scalar values
   *
   * @param array  $values      The values to format
   * @param string $human_label A human name for the entire list. If each
   *   value needs a separate label, then formatRecord should be used.
   * @return void
   */
  public function formatValueList(array $values, $human_label = '') {
    $this->formatValue(implode(', ', $values), $human_label);
  }

  /**
   * Formats data into a table for display
   *
   * @param array      $data    Data to format into a table
   * @param array|null $headers Headers to replace array/object keys
   * @return string
   */
  private function formatTable(array $data, array $headers = null) {
    $table = new \cli\Table();

    if ($headers) {
      $table->setHeaders($headers);
    }
    foreach ($data as $row_data) {
      $row = array();
      foreach ((array)$row_data as $key => $value) {
        $value = PrettyFormatter::flattenValue($value);
        $row[] = $value;
      }
      $table->addRow($row);
    }

    // @TODO: This does not test well. PHPUnit uses output buffering.
    ob_start();
    $table->display();
    $out = ob_get_contents();
    ob_end_clean();
    return $out;
  }

  /**
   * Flatten a value for display
   *
   * @param mixed $value        Value to stringify
   * @param array $human_labels Human-readable labels to replace keys with
   * @return string
   */
  private static function flattenValue($value, array $human_labels = array()) {
    if (is_scalar($value)) {
      return $value;
    }

    // Merge an array or object down. Doesn't look great past 2 levels of depth.
    $is_assoc = is_array($value)
      && (bool)count(array_filter(array_keys($value), 'is_string'));
    if ($is_assoc || is_object($value)) {
      foreach ($value as $key => $val) {
        $value[$key] = $key . ': '
          . PrettyFormatter::flattenValue($val, $human_labels);
      }
    }
    $value = join(', ', (array)$value);
    return $value;
  }

  /**
   * Gets the human name for a key, if available
   *
   * @param string $key          Array key referencing some data item
   * @param array  $human_labels Human-readable labels keyed to data
   * @return string
   */
  private static function getHumanLabel($key, array $human_labels) {
    $label = ucwords(strtr($key, '_', ' '));
    if (isset($human_labels[$key])) {
      $label = $human_labels[$key];
    }
    return $label;
  }

}
