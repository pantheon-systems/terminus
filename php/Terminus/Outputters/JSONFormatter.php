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
   * Formats a single scalar value with an optional human label.
   *
   * @param mixed $value
   *  The scalar value to format
   * @param string $human_label
   *  The human readable label for the value
   * @return string
   */
  public function formatValue($value, $human_label = '') {
    return json_encode($value, $this->json_options);
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @param array $human_labels
   *   A key/value array mapping the keys in the record to human labels
   * @return string
   */
  public function formatRecord($record, $human_labels = array()) {
    return json_encode((array)$record, $this->json_options);
  }

  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @param string $human_label
   *  A human name for the entire list. If each value needs a separate label then
   *  formatRecord should be used.
   * @return string
   */
  public function formatValueList($values, $human_label = '') {
    return json_encode((array)$values, $this->json_options);
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @param array $human_labels
   *  An array that maps the record keys to human names.
   * @return string
   */
  public function formatRecordList($records, $human_labels = array()) {
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

  /**
   * Format a message to the user.
   *
   * @param string $level
   * @param string $message
   * @param array $context
   * @return string
   */
  public function formatMessage($level, $message, $context = array()) {
    $object = array(
      'level' => $level,
      'message' => $message,
      'context' => $context
    );
    return $this->formatRecord($object) . PHP_EOL;
  }
}
