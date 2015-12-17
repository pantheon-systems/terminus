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
   * Outputs any variable type as a raw dump
   *
   * @param object|array $object Item to dump information on
   * @return void
   */
  public function outputDump($object);

  /**
   * Formats a single record or object
   *
   * @param array|object $record       A key/value array or object
   * @param array        $human_labels A key/value array mapping the keys in
   *   the record to human labels
   * @return void
   */
  public function outputRecord($record, array $human_labels = array());

  /**
   * Formats a list of records of the same type
   *
   * @param array $records      A list of arrays or objects.
   * @param array $human_labels An array that maps record keys to human names
   * @return void
   */
  public function outputRecordList(array $records, array $human_labels = array());

  /**
   * Formats a single scalar value with an optional human label
   *
   * @param mixed  $value       The scalar value to format
   * @param string $human_label The human readable label for the value
   * @return void
   */
  public function outputValue($value, $human_label = '');

  /**
   * Formats a list of scalar values
   *
   * @param array  $values      The values to format
   * @param string $human_label One human name for the entire list. If each
   *   value needs a separate label, then formatRecord should be used.
   * @return void
   */
  public function outputValueList(array $values, $human_label = '');

  /**
   * Sets the formatter which converts the output to a useful string
   *
   * @param OutputFormatterInterface $formatter Formatter selected for use
   * @return void
   */
  public function setFormatter(OutputFormatterInterface $formatter);

  /**
   * Sets the writer which sends the output to its final destination
   *
   * @param OutputWriterInterface $writer Writer selected for use
   * @return void
   */
  public function setWriter(OutputWriterInterface $writer);

}
