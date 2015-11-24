<?php

namespace Behat\Behat\Formatter;

use Behat\Behat\DataCollector\LoggerDataCollector;
use Behat\Behat\Definition\DefinitionInterface;
use Behat\Behat\Formatter\HtmlFormatter;
use Behat\Gherkin\Node\AbstractScenarioNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioNode;
use Behat\Gherkin\Node\StepNode;

class TerminusHtmlFormatter extends HtmlFormatter {

  private $_feature_info;

  /**
    * Get HTML template.
    *
    * @return [string] $html Template for report page
    */
  protected function getHtmlTemplate() {
    $template_path = $this->parameters->get('template_path');
    if (!$template_path) {
      $template_path = $this->parameters->get('support_path') . DIRECTORY_SEPARATOR . 'html.tpl';
    }

    if (file_exists($template_path)) {
      return file_get_contents($template_path);
    }

    $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
      <html xmlns ="http://www.w3.org/1999/xhtml">
        <head>
          <meta http-equiv="Content-Type" 
          content="text/html;charset=utf-8"/>
          <title>Behat Test Suite</title>
        </head>
        <body>
          <div id="behat">
            {{content}}
          </div>
        </body>
      </html>';
    return $html;
  }

  /**
    * Prints feature footer.
    *
    * @param FeatureNode $feature Current feature node
    * @return [void]
    */
  protected function printFeatureFooter(FeatureNode $feature) {
    $this->writeln('</table>');
  }

  /**
    * Prints feature header.
    *
    * @param FeatureNode $feature Current feature node
    * @return [void]
    *
    * @uses printFeatureOrScenarioTags()
    * @uses printFeatureName()
    * @uses printFeatureDescription()
    */
  protected function printFeatureHeader(FeatureNode $feature) {
    $this->writeln('<table style="' . $this->_getTableStyles() . '">');
    $this->writeln(
      '<colgroup>
      <col width="287">
      <col width="121">
      <col width="157">
      <col width="77">
      <col width="288">
      </colgroup>'
    );
    $this->writeln(
      '<tr>
      <th colspan=5 rowspan=1 style="' . $this->_getTitleStyles() . '">
      Pantheon: ' . $feature->getTitle() . '<br>'
      . date('Y-m-d')
      . '</th>
      </tr>'
    );

    $this->writeln('<tr>');
    $headers = array(
      'Core Experience',
      'Status',
      'Errors',
      'Workflow ID',
      'Comments'
    );
    foreach ($headers as $header) {
      $this->writeln(
        '<th style="' . $this->_getHeaderStyles() . '">' . $header . '</th>'
      );
    }
  }

  /**
    * Prints scenario footer.
    *
    * @param ScenarioNode $scenario Current scenario object
    * @return [void]
    */
  protected function printScenarioFooter(ScenarioNode $scenario) {
    $info = $this->_feature_info;
    $end_result = $this->_getStatus($info['result']);
    $message = '';
    if (isset($info['message'])) {
      $message = $info['message'];
    }

    $this->writeln(
      '<td style="' . $this->_getCellStyle() . '">'
      . $scenario->getTitle()
      . '</td>
      <td style="' . $this->_getCellStyle($end_result) . '">'
      . $end_result
      . '</td>
      <td style="' . $this->_getCellStyle() . '">' . $message . '</td>
      <td style="' . $this->_getCellStyle() . '"></td>
      <td style="' . $this->_getCellStyle() . '"></td>
      </tr>'
    );

    echo 'Test ' . $end_result . PHP_EOL . PHP_EOL;
  }

  /**
    * Prints scenario header.
    *
    * @param ScenarioNode $scenario Current scenario object
    * @return [void]
    *
    * @uses printFeatureOrScenarioTags()
    * @uses printScenarioName()
    */
  protected function printScenarioHeader(ScenarioNode $scenario) {
    $this->_feature_info = '';
    $this->writeln('<tr>');
    echo $scenario->getTitle() . PHP_EOL;
  }

  /**
    * Prints scenario keyword and name.
    *
    * @param AbstractScenarioNode $scenario Current scenario object
    * @return [void]
    *
    * @uses getFeatureOrScenarioName()
    * @uses printScenarioPath()
    */
  protected function printScenarioName(AbstractScenarioNode $scenario) {
    $this->writeln($scenario->getTitle());
  }

  /**
    * Prints summary suite run information.
    *
    * @param LoggerDataCollector $logger suite logger
    * @return [void]
    */
  protected function printSummary(LoggerDataCollector $logger) {
  }

  /**
    * Prints step.
    *
    * @param [StepNode]            $step       step node
    * @param [integer]             $result     step result code
    * @param [DefinitionInterface] $definition def instance (if step defined)
    * @param [string]              $snippet    snippet (if step is undefined)
    * @param [\Exception]          $exception  exception (if step is failed)
    * @return [void]
    *
    * @uses StepEvent
    */
  protected function printStep(
      StepNode $step,
      $result,
      DefinitionInterface $definition = null,
      $snippet = null,
      \Exception $exception = null
  ) {
    $this->_feature_info = array(
      'result' => $result,
      'text'   => $step->getText()
    );
    if ($exception != null) {
      $this->_feature_info['message'] = $exception->getMessage();
    }
  }

  /**
    * Returns inline styles for cells
    *
    * @param [string] $status none/passed/failed/skipped
    * @return [string] $css Styles for cells
    */
  private function _getCellStyle($status = 'none') {
    $colors = array(
      'none' => 'rgb(255, 255, 255)',
      'passed' => 'rgb(0, 255, 0)',
      'failed' => 'rgb(255, 0, 0)',
      'skipped' => 'rgb(200, 200, 200)',

    );
    if (!isset($colors[$status])) {
      $status = 'none';
    }

    $css = 'padding: 2px 3px;
    vertical-align: bottom;
    border-right-width: 1px;
    border-right-style: solid;
    border-right-color: rgb(0,0,0);
    border-bottom-width: 1px;
    border-bottom-style: solid;
    border-bottom-color: rgb(0,0,0);
    background-color: ' . $colors[$status] . ';';
    return $css;
  }

  /**
    * Returns inline styles for report headers
    *
    * @return [string] $css Styles for report headers
    */
  private function _getHeaderStyles() {
    $css = 'text-transform: uppercase;
    padding: 2px 3px;
    background-color: rgb(183,183,183);
    font-size: 100%;
    font-weight: bold;
    text-decoration: none;
    color: rgb(0,0,0);
    vertical-align: bottom;
    text-align: center;
    border-right-width: 1px;
    border-right-style: solid;
    border-right-color: rgb(0,0,0);
    border-bottom-width: 1px;
    border-bottom-style: solid;
    border-bottom-color: rgb(0,0,0);';
    return $css;
  }

  /**
    * Returns inline styles for table headers
    *
    * @return [string] $css Styles for table headers
    */
  private function _getTableStyles() {
    $css = 'table-layout: fixed;
    font-size: 13px;
    font-family: arial, sans, sans-serif;
    border-collapse: collapse;
    border: 1px solid rgb(204, 204, 204);';
    return $css;
  }

  /**
    * Returns inline styles for table headers
    *
    * @return [string] $css Styles for table headers
    */
  private function _getTitleStyles() {
    $css = 'text-transform: uppercase;
    padding: 2px 3px;
    background-color: rgb(0,0,0);
    border: 1px solid rgb(0,0,0);
    font-size: 100%;
    font-weight: bold;
    color: rgb(239, 239, 239);
    vertical-align: bottom;
    text-align: center;';
    return $css;
  }

  /**
    * Returns result of test step
    *
    * @param [array] $result Array generated by parent formatter
    * @return [string] $result skipped/passed/failed/etc
    */
  private function _getStatus($result) {
    preg_match("/I \"(.*)\" the test/", $this->_feature_info['text'], $matches);
    if (empty($matches)) {
      $result = $this->getResultColorCode($result);
    } else {
      $result = $matches[1];
    }
    return $result;
  }

}
