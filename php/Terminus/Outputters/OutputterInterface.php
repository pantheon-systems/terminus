<?php
/**
 * @file
 */

namespace Terminus\Outputters;


/**
 * Interface OutputterInterface
 * @package Terminus\Outputters
 */
interface OutputterInterface {

  /**
   * Set the writer which sends the output to it's final destination.
   *
   * @param OutputWriterInterface $writer
   */
  public function setWriter(OutputWriterInterface $writer);

  /**
   * Set the formatter which converts the output to a useful string.
   *
   * @param OutputFormatterInterface $formatter
   */
  public function setFormatter(OutputFormatterInterface $formatter);

  /**
   * Formats a single scalar value with an optional human label.
   *
   * @param mixed $value
   *  The scalar value to format
   * @param string $human_label
   *  The human readable label for the value
   * @return string
   */
  public function outputValue($value, $human_label = '');

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @param array $human_labels
   *   A key/value array mapping the keys in the record to human labels
   * @return string
   */
  public function outputRecord($record, $human_labels = array());


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
  public function outputValueList($values, $human_label = '');

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @param array $human_labels
   *  An array that maps the record keys to human names.
   * @return string
   */
  public function outputRecordList($records, $human_labels = array());

  /**
   * Output any variable type as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function outputDump($object);

  /**
   * Output a message to the user.
   *
   * @param string $level
   * @param string $message
   * @param array $context
   * @return
   */
  public function outputMessage($level, $message, $context = array());
}