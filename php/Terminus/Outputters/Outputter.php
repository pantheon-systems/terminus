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
   * Formats a single scalar value.
   *
   * @param mixed $value
   *  The scalar value to format
   * @return string
   */
  public function outputValue($value) {
    $this->getWriter()->write($this->getFormatter()->formatValue($value));
  }

  /**
   * Format a single record or object
   *
   * @param array|object $record
   *   A key/value array or object
   * @return string
   */
  public function outputRecord($record) {
    $this->getWriter()->write($this->getFormatter()->formatRecord($record));
  }

  /**
   * Format a list of scalar values
   *
   * @param array $values
   *  The values to format
   * @return string
   */
  public function outputValueList($values) {
    $this->getWriter()->write($this->getFormatter()->formatRecord($values));
  }

  /**
   * Format a list of records of the same type.
   *
   * @param array $records
   *  A list of arrays or objects.
   * @return string
   */
  public function outputRecordList($records) {
    $this->getWriter()->write($this->getFormatter()->formatRecordList($records));
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
