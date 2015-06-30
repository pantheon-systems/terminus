<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException,
    Behat\Behat\Event\SuiteEvent,
    Behat\Behat\Event\ScenarioEvent;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode,
    Behat\Gherkin\Node\ScenarioNode;

use VCR\VCR;

/**
 * Features context.
 */
class FeatureContext extends BehatContext {
    public $cliroot = '';
    private $testroot = 'tests';
    private $cassette_name = ''; //Find a better way to do this. DO NOT PUSH BEFORE FINDING IT, SARA.
    private static $vcr_format = 'json';

    /**
    * Initializes context.
    * Every scenario gets it's own context object.
    * 
    * @param $parameters [array] context parameters (set them up through behat.yml)
    */
    public function __construct(array $parameters) {
        $this->cliroot = dirname(dirname(__DIR__));
        
        // Initialize your context here
    }

    /**
    * @Given /^I am in directory "([^"]*)"$/
    * 
    * @param $command [string]
    */
    public function iAmInDirectory($dir) {
      if( !file_exists('bin/terminus') ) {
        //must be in the wrong directory so chdir
        chdir($this->cliroot);
      }
      return true;

    }

    /**
    * @When /^I run "([^"]*)"$/
    * 
    * @param $command [string]
    */
    public function iRun($command) {
      // replace terminus with the path to the php
      // and pipe stderr to stdout
      VCR::insertCassette($this->cassette_name);
      $terminus_cmd = sprintf('bin/terminus', $this->cliroot);
      $command = str_replace("terminus", $terminus_cmd, $command);
      $this->output = $this->response($command);
      VCR::eject();
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
    * @Then /^I enter "([^"]*)"$/ 
    * 
    * @param $string [string]
    */
    public function iEnter($string) {
      $fh = fopen("php://stdin", 'w');
      fwrite($fh, "$string\n");
    }

    /**
    * Configures VCR before test are run
    * 
    * @param $event [SuiteEvent]
    */
    public static function prepare($event) {
      \VCR\VCR::configure()->enableRequestMatchers(array('method', 'url', 'body'));

      # Prevent API requests from being made in CI Environment
      VCR::configure()->setMode('none');
      VCR::configure()->setStorage(FeatureContext::$vcr_format);
    }

    /**
    * @BeforeScenario 
    * Turns on VCR before each feature call
    * 
    * @param $event [SuiteEvent]
    */
    public function before($event) {
      VCR::turnOn();
      $this->cassetteName($event);
    }

    /**
    * @AfterScenario
    * Turns off VCR after each feature call
    * 
    * @param $event [SuiteEvent]
    */
    public function after($event) {
      VCR::turnOff();
    }

    /**
    * Returns name of the cassette to be used.
    * 
    * @param $event [SuiteEvent]
    * @return [string] Of scneario name, lowercase, with underscores and suffix
    */
    private function cassetteName($event) {
      $tags = $this->readTags($event);
      $this->cassette_name = $tags['vcr'];
      return $this->cassette_name;
    }


    /**
    * Records output of command
    * 
    * @param $command
    * @return $response [array] of the lines of the response
    * @return [boolean] false if the fixture file does not exist
    */
    private function response($command) {
      $fixture = $this->testroot . '/fixtures/' . $this->cassette_name; //SARA: FIND A BETTER WAY
      if(!file_exists($fixture)) return false; //SARA: Implement some sort of error return? Necessary?

      $file_contents = trim(file_get_contents($fixture));
      if($file_contents == '') {
        $output = shell_exec($command); 
        file_put_contents($fixture, $this->encode($output, FeatureContext::$vcr_format));
        chmod($fixture, 0777);
        if($output == '') die($command);
      } else {
        $output = $this->decode($file_contents, FeatureContext::$vcr_format);
      }
      return $output;
    }


    /**
    * Returns tags in easy-to-use array format.
    * 
    * @param $event [SuiteEvent]
    * @return $tags [array] An array of strings corresponding to tags
    */
    private function readTags($event) {
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
    * Returns the data in either JSON or YAML format
    * 
    * @param $data [mixed] data to be encoded
    * @param $format [string] Either JSON or YAML
    * @return $output [string] 
    */
    private function encode($data, $format) {
      switch($format) {
        case 'json':
          $function_name = 'json_encode';
          break;
        case 'yaml':
          $function_name = 'yaml_parse';
          break;
        default:
          return $data;
      }

      if(function_exists($function_name)) return $function_name($data);
      return $data;
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
          break;
        case 'yaml':
          $function_name = 'yaml_emit';
          break;
        default:
          return $data;
      }

      if(function_exists($function_name)) return $function_name($data);
      return $data;
    }

// Place your definition and hook methods here:
    /**
    * @Given /^I have done something with "([^"]*)"$/
    * 
    * @param $argument [string] data to be encoded
    */
//  public function iHaveDoneSomethingWith($argument) {
//    doSomethingWith($argument);
//  }

}
