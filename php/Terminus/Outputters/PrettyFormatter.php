<?php
/**
 * @file
 * Contains Terminus\Outputters\PrettyFormatter
 */


namespace Terminus\Outputters;

use Terminus\Internationalizer as I18n;


class PrettyFormatter implements OutputFormatterInterface {

  /**
   * Formats a single scalar value.
   *
   * @param [mixed] $value
   *  The scalar value to format
   * @param [string] $label Key for label to look up
   * @return [string] $human_label
   */
  public function formatValue($value, $label = '') {
    $value = PrettyFormatter::flattenValue($value);
    $label = self::getHumanLabel($label, $autolabel = false);

    $human_label = $value;
    if ($label) {
      $human_label = "$label: $value";
    }
    return $human_label;
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function formatRecord($record) {
    // No output for empty records. This should be handled by the logger with a friendly message.
    if (empty($record)) {
      return '';
    }

    // Normalize the keys
    $human_labels = array_keys($record);
    $rows = array();

    // Get the headers
    $record = (array)$record;
    foreach ((array)$record as $key => $value) {
      $label = self::getHumanLabel($key);
      $rows[] = array($label, $value);
    }

    return $this->formatTable($rows, array('Key', 'Value'));
  }

  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function formatValueList($values) {
    $this->formatValue(implode(', ', $values));
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function formatRecordList($records) {
    // No output for empty records. This should be handled by the logger with a friendly message.
    if (empty($records)) {
      return '';
    }

    // TODO: Implement formatRecordList() method.
    // Normalize the keys
    $keys = array();
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
      $new = array();
      $record = (array)$record;
      foreach ($keys as $key) {
        $new[$key] = isset($record[$key]) ? PrettyFormatter::flattenValue($record[$key]) : '';
      }
      $records[$i] = $new;
    }
    // Get the headers
    foreach ($keys as $key) {
      $header[] = self::getHumanLabel($key);
    }

    return $this->formatTable($records, $header);
  }

  /**
   * @param $data
   * @param $headers
   */
  private function formatTable($data, $headers = null) {
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
   * Format any kind of value as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function formatDump($object) {
    return print_r($object, true);
  }

  /**
   * Get the human name for a key if available.
   *
   * @param [string] $key
   * @param [boolean] $autolabel If true, will return a label made from the key
   * @return [string] $label
   */
  private static function getHumanLabel($key, $autolabel = true) {
    $i18n  = new I18n();
    $label = $i18n->get($key);
    if ($label == '') {
      if (!$autolabel) {
        return false;
      }
      $label = ucwords(strtr($key, '_', ' '));
    }
    return $label;
  }

  /**
   * Flatten a value for display
   *
   * @param mixed $value
   * @return string
   */
  private static function flattenValue($value) {
    if (is_scalar($value)) {
      return $value;
    }

    // Merge an array or object down. Doesn't look great past 2 levels of depth.
    $is_assoc = is_array($value) && (bool)count(array_filter(array_keys($value), 'is_string'));
    if ($is_assoc || is_object($value)) {
      foreach ($value as $key => $val) {
        $value[$key] = PrettyFormatter::getHumanLabel($key) . ': ' . PrettyFormatter::flattenValue($val);
      }
    }
    $value = join(', ', (array)$value);
    return $value;
  }

}
