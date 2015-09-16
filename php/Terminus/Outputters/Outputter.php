<?php
/**
 * @file
 * Contains Terminus\Outputters\Outputter
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
   * @param OutputWriterInterface $writer
   * @param OutputFormatterInterface $formatter
   */
  public function __construct(OutputWriterInterface $writer, OutputFormatterInterface $formatter) {
    $this->setFormatter($formatter);
    $this->setWriter($writer);
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
  public function outputValue($value, $human_label = '') {
    $this->getWriter()->write($this->getFormatter()->formatValue($value, $human_label));
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
  public function outputRecord($record, $human_labels = array()) {
    $this->getWriter()->write($this->getFormatter()->formatRecord($record, $human_labels));
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
  public function outputValueList($values, $human_label = '') {
    $this->getWriter()->write($this->getFormatter()->formatRecord($values, $human_label));
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
  public function outputRecordList($records, $human_labels = array()) {
    $this->getWriter()->write($this->getFormatter()->formatRecordList($records, $human_labels));
  }

  /**
   * Output any variable type as a raw dump.
   *
   * @param $object
   * @return string
   */
  public function outputDump($object) {
    $this->getWriter()->write($this->getFormatter()->formatDump($object));
  }

  /**
   * @return OutputWriterInterface
   */
  public function getWriter() {
    return $this->writer;
  }

  /**
   * @return OutputFormatterInterface
   */
  public function getFormatter() {
    return $this->formatter;
  }

  /**
   * @param OutputFormatterInterface $formatter
   */
  public function setFormatter(OutputFormatterInterface $formatter) {
    $this->formatter = $formatter;
  }

  /**
   * @param OutputWriterInterface $writer
   */
  public function setWriter(OutputWriterInterface $writer) {
    $this->writer = $writer;
  }
}
