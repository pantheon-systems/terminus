<?php

use Behat\Behat\Context\ClosuredContextInterface;
use Behat\Behat\Context\TranslatedContextInterface;
use Behat\Behat\Context\BehatContext;
use Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Gherkin\Node\ScenarioNode;

/**
 * Features context for Behat feature testing
 */
class FeatureContext extends BehatContext {
    public $cliroot = '';
    private $_cassette_name;
    private $_connection_info;
    private $_parameters;
    private $_output;

    /**
    * Initializes context. Sets directories for navigation.
    * 
    * @param [array] $parameters Context parameters, set through behat.yml
    * @return [void]
    */
    public function __construct(array $parameters) {
      $this->cliroot          = dirname(dirname(__DIR__)) . '/..';
      $this->_parameters      = $parameters;
      $this->_connection_info = array(
        'username' => $parameters['username'],
        'password' => $parameters['password'],
        'host'     => $parameters['host']
      );
    }

    /**
    * @BeforeScenario
    * Runs before each scenario
    * 
    * @param [ScenarioEvent] $event Feature information from Behat
    * @return [void]
    */
    public function before($event) {
      $this->_setCassetteName($event);
    }

    /**
    * @Then /^I enter "([^"]*)"$/ 
    * 
    * @param [string] $string To be treated as CL stdin
    * @return [void]
    */
    public function iEnter($string) {
      $fh = fopen("php://stdin", 'w');
      fwrite($fh, "$string\n");
    }

    /**
    * @When /^I run "([^"]*)"$/
    * Runs command and saves output
    * 
    * @param [string] $command To be entered as CL stdin
    * @return [void]
    */
    public function iRun($command) {
      $command      = $this->_replacePlaceholders($command);
      $regex        = '/(?<!\.)terminus/';
      $terminus_cmd = sprintf('bin/terminus', $this->cliroot);
      $command      = 'VCR_CASSETTE=' . $this->_cassette_name 
        . ' ' . preg_replace($regex, $terminus_cmd, $command);
      if(isset($this->_parameters['vcr_mode'])) {
        $command = 'VCR_MODE=' . $this->_parameters['vcr_mode'] 
          . ' ' . $command;
      }
      if(isset($this->_connection_info['host'])) {
        $command = 'TERMINUS_HOST=' . $this->_connection_info['host'] 
          . ' ' . $command;
      }
      $this->_output = shell_exec($command);
    }

    /**
    * @Then /^I should get:$/ 
    * Swap in $this->_parameters elements by putting them in [[double brackets]]
    * 
    * @param [PyStringNode] $string Content which ought not be in the output
    * @return [void]
    */
    public function iShouldGet(PyStringNode $string) {
      if(!$this->_checkResult((string)$string, $this->_output)) {
        throw new Exception("Actual output:\n" . $this->_output);
      }
    }

    /**
    * @Then /^I should not get:$/ 
    * 
    * @param [PyStringNode] $string Content which ought not be in the output
    * @return [void]
    */
    public function iShouldNotGet(PyStringNode $string) {
      if($this->_checkResult((string)$string, $this->_output)) {
        throw new Exception("Actual _output:\n" . $this->_output);
      }
    }

    /**
    * Checks the the haystack for the needle
    * 
    * @param [string] $needle   That which is searched for
    * @param [string] $haystack That which is searched inside
    * @return [boolean] $result True if $nededle was found in $haystack
    */
    private function _checkResult($needle, $haystack) {
      $needle = $this->_replacePlaceholders($needle);
      $result = preg_match("#" . preg_quote($needle . "#s"), $haystack);
      return $result;
    }

    /**
    * Returns tags in easy-to-use array format.
    * 
    * @param [ScenarioEvent] $event Feature information from Behat
    * @return $tags [array] An array of strings corresponding to tags
    */
    private function _getTags($event) {
      $unformatted_tags = $event->getScenario()->getTags();
      $tags = [];

      foreach($unformatted_tags as $tag) {
        $tag_elements = explode(' ', $tag);
        $index        = null;
        if(count($tag_elements < 1)) {
          $index = array_shift($tag_elements);
        }
        if(count($tag_elements == 1)) {
          $tag_elements = array_shift($tag_elements);
        }
        $tags[$index] = $tag_elements;
      }

      return $tags;
    }

    /**
    * Exchanges values in given string with square brackets for values
    * in $this->_parameters
    * 
    * @param [string] $string The string to perform replacements on
    * @return [string] $string The modified param string
    */
    private function _replacePlaceholders($string) {
      $regex = '~\[\[(.*?)\]\]~';
      preg_match_all($regex, $string, $matches);

      foreach($matches[1] as $id => $replacement_key) {
        if(isset($this->_parameters[$replacement_key])) {
          $replacement = $this->_parameters[$replacement_key];
          $string      = str_replace($matches[0][$id], $replacement, $string);
        }
      }

      return $string;
    }

    /**
    * Sets $this->_cassette_name and returns name of the cassette to be used.
    * 
    * @param [ScenarioEvent] $event Feature information from Behat
    * @return [string] Of scneario name, lowercase, with underscores and suffix
    */
    private function _setCassetteName($event) {
      $tags = $this->_getTags($event);
      $this->_cassette_name = $tags['vcr'];
      return $this->_cassette_name;
    }
}
