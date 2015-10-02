<?php

namespace Terminus\Inputters;

use Terminus\Internationalizer as I18n;
use Terminus\Outputters\Outputter;

class Inputter {

  /**
   * @var Outputter
   */
  private $outputter;

  /**
   * Constructs inputter
   *
   * @param [Outputter] $outputter
   * @return [Inputter] $this
   */
  public function __construct(Outputter $outputter) {
    $this->outputter = $outputter;
  }

  /**
   * Outputs a prompt and collects the result
   *
   * @param [string] $key     I18n key for output
   * @param [array]  $context Replacements for variables in i18n string
   * @return $response
   */
  public function promptForInput($key, $context = array()) {
    $i18n = new I18n();
    $this->outputter->getWriter()->write($i18n->get($key, $context));
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

}
