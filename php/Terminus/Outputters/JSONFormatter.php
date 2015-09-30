<?php
/**
 * @file
 * Contains Terminus\Outputters\JSON
 */


namespace Terminus\Outputters;

/**
 * Class JSONFormatter
 * @package Terminus\Outputters
 */
class JSONFormatter implements OutputFormatterInterface {

  /**
   * @var int
   *  The options to be passed to json_encode.
   *  See: http://php.net/manual/en/function.json-encode.php
   */
  protected $json_options;

  /**
   * @param int $options
   *  The json_encode options bitmask.
   */
  public function __construct($options = 0) {
    $this->json_options = $options;
  }

  /**
   * Formats a single scalar value.
   *
   * @param mixed $value
   *  The scalar value to format
   * @return string
   */
  public function formatValue($value) {
    return json_encode($value, $this->json_options);
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function formatRecord($record) {
    return json_encode((array)$record, $this->json_options);
  }

  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function formatValueList($values) {
    return json_encode((array)$values, $this->json_options);
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function formatRecordList($records) {
    return json_encode((array)$records, $this->json_options);
  }

  /**
   * Format any kind of value as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function formatDump($object) {
    return json_encode($object, $this->json_options);
  }
}
