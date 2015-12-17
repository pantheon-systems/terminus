<?php

/**
 * @file
 */

namespace Terminus\Outputters;

/**
 * Interface OutputFormatterInterface
 * @package Terminus\Outputters
 */
interface OutputFormatterInterface
{

  /**
   * Formats any kind of value as a raw dump
   *
   * @param mixed $object An object to dump via print_r
   * @return string
   */
  public function formatDump($object);

  /**
   * Format a single record or object
   *
   * @param array|object $record       A key/value array or object
   * @param array        $human_labels A key/value array mapping the keys in
   *   the record to human labels
   * @return string
   */
  public function formatRecord($record, array $human_labels = array());

  /**
   * Format a list of records of the same type.
   *
   * @param array $records      A list of arrays or objects.
   * @param array $human_labels An array mapping record keys to human names
   * @return string
   */
  public function formatRecordList(array $records, array $human_labels = array());

  /**
   * Formats a single scalar value with an optional human label.
   *
   * @param mixed  $value       A scalar value to format
   * @param string $human_label A human readable label for that value
   * @return string
   */
  public function formatValue($value, $human_label = '');

  /**
   * Format a list of scalar values
   *
   * @param array  $values      The values to format
   * @param string $human_label A human name for the entire list. If each value
   *   needs a separate label, then formatRecord should be used.
   * @return void
   */
  public function formatValueList(array $values, $human_label = '');

}
