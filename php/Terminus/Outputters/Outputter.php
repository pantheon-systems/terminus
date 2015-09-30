<?php
/**
 * @file
 * Contains Terminus\Outputters\Outputter
 */


namespace Terminus\Outputters;

use Terminus\Internationalizer as I18n;


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
   * @var I18n
   */
  protected $i18n;

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
    $this->setInternationalizer();
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
   * Outputs a prompt and collects the result
   *
   * @param [string] $key     I18n key for output
   * @param [array]  $context Replacements for variables in i18n string
   * @return $response
   */
  public function promptForInput($key, $context = array()) {
    $this->getWriter()->write($this->i18n->get($key, $context));
    if (strpos($key, 'password') === false) {
      $response = $this->getInput();
    } else {
      $response = $this->getInputSilently();
    } 
    return $response;
  }

  /**
   * Gets input from STDIN
   *
   * @return [string] $response
   */
  private function getInput() {
    $line = readline();
    $response = trim($line);
    return $response;
  }

  /**
   * Gets input from STDIN silently
   * By: Troels Knak-Nielsen
   * From: http://www.sitepoint.com/interactive-cli-password-prompt-in-php/
   *
   * @return $password
   */
  private function getInputSilently() {
    if (preg_match('/^win/i', PHP_OS)) {
      $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
      file_put_contents(
        $vbscript, 'wscript.echo(InputBox("'
        . addslashes($prompt)
        . '", "", "password here"))');
      $command = "cscript //nologo " . escapeshellarg($vbscript);
      $password = rtrim(shell_exec($command));
      unlink($vbscript);
      return $password;
    } else {
      $command = "/usr/bin/env bash -c 'echo OK'";
      if (rtrim(shell_exec($command)) !== 'OK') {
        trigger_error("Can't invoke bash");
        return;
      }
      $command = "/usr/bin/env bash -c 'read -s -p \""
        . addslashes($prompt)
        . "\" mypassword && echo \$mypassword'";
      $password = rtrim(shell_exec($command));
      echo "\n";
      return $password;
    }
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
   * @return [void]
   */
  public function setInternationalizer() {
    $this->i18n = new I18n();
  }

  /**
   * @param OutputWriterInterface $writer
   */
  public function setWriter(OutputWriterInterface $writer) {
    $this->writer = $writer;
  }
}
