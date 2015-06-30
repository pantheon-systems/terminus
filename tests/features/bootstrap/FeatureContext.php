<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Node\ScenarioNode;

/**
 * Features context.
 */
class FeatureContext extends BehatContext {
    public $cliroot = '';
    private $cassette_name;
    private $fixtures_dir = '/tests/fixtures'; //SARA: find out how to get this from VCR
    private $output;

    /**
    * Initializes context. Sets directories for navigation.
    * 
    * @param $parameters [array] context parameters (set them up through behat.yml)
    */
    public function __construct(array $parameters) {
      $this->cliroot = dirname(dirname(__DIR__)) . '/..';
    }

    /**
    * @BeforeScenario
    * Runs before each scenario
    * 
    * @param $event [ScenarioEvent]
    */
    public function before($event) {
      $this->setCassetteName($event);
    }

    /**
    * @Given /^I am in directory "([^"]*)"$/
    * Changes the directory to given subdir of Terminus root directory
    * 
    * @param $dir [string]
    */
    public function iAmInDirectory($dir) {
      chdir($this->cliroot . $dir);
      return true;
    }

    /**
    * @Then /^I enter "([^"]*)"$/ 
    * 
    * @param $string [string]
    */
    public function iEnter($string) {
      $fh = fopen("php://stdin", 'w');
      fwrite($fh, "$string\n");
    }

    /**
    * @When /^I run "([^"]*)"$/
    * Runs command and saves output
    * 
    * @param $command [string]
    */
    public function iRun($command) {
      if(!file_exists($this->getCassetteFilename())) {
        $terminus_cmd = sprintf('bin/terminus', $this->cliroot);
        $command = 'VCR_CASSETTE=' . $this->cassette_name . ' ' . str_replace("terminus", $terminus_cmd, $command) . ' &';
        shell_exec($command);
      }
      $output = $this->getOutput();
      $this->output = $output['response']['body'];
    }

    /**
    * @Then /^I should get:$/ 
    * 
    * @param $string [string]
    */
    public function iShouldGet(PyStringNode $string) {
      if (!preg_match("#" . preg_quote((string) $string) . "#s", $this->output)) {
        throw new Exception(
          "Actual output is:\n" . $this->output
        );
      }
    }

    /**
    * Returns cassette filename
    *
    * @return [string] cassette filename full path
    */
    private function getCassetteFilename() {
      return $this->cliroot . '/' . $this->fixtures_dir . '/' . $this->cassette_name;
    }

    /**
    * Returns tags in easy-to-use array format.
    * 
    * @param $event [ScenarioEvent]
    * @return $tags [array] An array of strings corresponding to tags
    */
    private function getTags($event) {
      $unformatted_tags = $event->getScenario()->getTags();
      $tags = [];

      foreach($unformatted_tags as $tag) {
        $tag_elements = explode(' ', $tag);
        $index = null;
        if(count($tag_elements < 1)) $index = array_shift($tag_elements);
        if(count($tag_elements == 1)) $tag_elements = array_shift($tag_elements);
        $tags[$index] = $tag_elements;
      }

      return $tags;
    }

    /**
    * Decodes the data from either JSON or YAML format
    * 
    * @param $data [string] data to be encoded
    * @param $format [string] Either JSON or YAML
    * @return $output [mixed] 
    */
    private function decode($data, $format) {
      switch($format) {
        case 'json':
          $function_name = 'json_decode';
          $format_argument = true;

          break;
        case 'yaml':
          $function_name = 'yaml_parse';
          $format_argument = -1;
          break;
        default:
          return $data;
      }

      if(function_exists($function_name)) return $function_name($data, $format_argument);
      return $data;
    }

    /**
    * Get contents of cassette
    *
    * @return [array] decoded cassette contents
    */
    private function getOutput() {
      $output = $this->decode(file_get_contents($this->getCassetteFilename()), 'json'); //SARA: find out how to get this from VCR
      return array_shift($output);
    }

    /**
    * Returns tags in easy-to-use array format.
    * 
    * @param $event [ScenarioEvent]
    * @return $tags [array] An array of strings corresponding to tags
    */
    private function getScenarioTags($event) {
      $unformatted_tags = $event->getScenario()->getTags();
      $tags = [];

      foreach($unformatted_tags as $tag) {
        $tag_elements = explode(' ', $tag);
        $index = null;
        if(count($tag_elements < 1)) $index = array_shift($tag_elements);
        if(count($tag_elements == 1)) $tag_elements = array_shift($tag_elements);
        $tags[$index] = $tag_elements;
      }

      return $tags;
    }

    /**
    * Sets $this->cassette_name and returns name of the cassette to be used.
    * 
    * @param $event [SuiteEvent]
    * @return [string] Of scneario name, lowercase, with underscores and suffix
    */
    private function setCassetteName($event) {
      $tags = $this->getScenarioTags($event);
      $this->cassette_name = $tags['vcr'];
      return $this->cassette_name;
    }
}
