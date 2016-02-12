<?php

/**
 * @file Contains Terminus\Outputters\Outputter
 */

namespace Terminus\Outputters;

/**
 * A base class which takes output, formats it and writes it.
 *
 * Class Outputter
 * @package Terminus\Outputters
 */
class Outputter implements OutputterInterface {

  /**
   * @var OutputFormatterInterface
   */
  protected $formatter;

  /**
   * @var OutputWriterInterface
   */
  protected $writer;

  /**
   * Object constructor. Sets writer and formatter properties.
   *
   * @param OutputWriterInterface    $writer    Writer object to set
   * @param OutputFormatterInterface $formatter Formatter object to set
   */
  public function __construct(
    OutputWriterInterface $writer,
    OutputFormatterInterface $formatter
  ) {
    $this->setFormatter($formatter);
    $this->setWriter($writer);
  }

  /**
   * Retrieves the set formatter object
   *
   * @return OutputFormatterInterface
   */
  public function getFormatter() {
    return $this->formatter;
  }

  /**
   * Retrieves the set writer object
   *
   * @return OutputWriterInterface
   */
  public function getWriter() {
    return $this->writer;
  }

  /**
   * Display a message in the CLI and end with a newline
   * TODO: Clean this up. There should be no direct access to STDOUT/STDERR
   *
   * @param string $message Message to output before the new line
   * @return void
   */
  public function line($message = '') {
    $this->getWriter()->write($message . PHP_EOL);
  }

  /**
   * Outputs any variable type as a raw dump
   *
   * @param object|array $object Item to dump information on
   * @return void
   */
  public function outputDump($object) {
    $this->getWriter()->write($this->getFormatter()->formatDump($object));
  }

  /**
   * Formats a single record or object
   *
   * @param array|object $record       A key/value array or object
   * @param array        $human_labels A key/value array mapping the keys in
   *   the record to human labels
   * @return void
   */
  public function outputRecord($record, array $human_labels = array()) {
    $this->getWriter()->write(
      $this->getFormatter()->formatRecord($record, $human_labels)
    );
  }

  /**
   * Formats a list of records of the same type
   *
   * @param array $records      A list of arrays or objects.
   * @param array $human_labels An array that maps record keys to human names
   * @return void
   */
  public function outputRecordList(array $records, array $human_labels = array()) {
    $this->getWriter()->write(
      $this->getFormatter()->formatRecordList($records, $human_labels)
    );
  }

  /**
   * Formats a single scalar value with an optional human label
   *
   * @param mixed  $value       The scalar value to format
   * @param string $human_label The human readable label for the value
   * @return void
   */
  public function outputValue($value, $human_label = '') {
    $this->getWriter()->write(
      $this->getFormatter()->formatValue($value, $human_label)
    );
  }

  /**
   * Formats a list of scalar values
   *
   * @param array  $values      The values to format
   * @param string $human_label One human name for the entire list. If each
   *   value needs a separate label, then formatRecord should be used.
   * @return void
   */
  public function outputValueList(array $values, $human_label = '') {
    $this->getWriter()->write(
      $this->getFormatter()->formatRecord($values, [$human_label])
    );
  }

  /**
   * Sets the formatter which converts the output to a useful string
   *
   * @param OutputFormatterInterface $formatter Formatter selected for use
   * @return void
   */
  public function setFormatter(OutputFormatterInterface $formatter) {
    $this->formatter = $formatter;
  }

  /**
   * Sets the writer which sends the output to its final destination
   *
   * @param OutputWriterInterface $writer Writer selected for use
   * @return void
   */
  public function setWriter(OutputWriterInterface $writer) {
    $this->writer = $writer;
  }

}
