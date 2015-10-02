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
   * Formats a single scalar value.
   *
   * @param mixed $value
   *  The scalar value to format
   * @return string
   */
  public function outputValue($value);

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function outputRecord($record);


  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function outputValueList($values);

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function outputRecordList($records);

  /**
   * Output any variable type as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function outputDump($object);
}
