<?php
/**
 * @file
 */

namespace Terminus\Outputters;


/**
 * Interface OutputFormatterInterface
 * @package Terminus\Outputters
 */
interface OutputFormatterInterface {

  /**
   * Formats a single scalar value.
   *
   * @param mixed $value
   *  The scalar value to format
   * @param [string] $label Key for label to look up
   * @return string
   */
  public function formatValue($value, $label);

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function formatRecord($record);


  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function formatValueList($values);

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function formatRecordList($records);

  /**
   * Format any kind of value as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function formatDump($object);
}
