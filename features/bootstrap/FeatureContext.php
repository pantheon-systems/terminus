<?php

use Behat\Behat\Context\ClosuredContextInterface,
    Behat\Behat\Context\TranslatedContextInterface,
    Behat\Behat\Context\BehatContext,
    Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

use VCR\VCR;

/**
 * Features context.
 */
class FeatureContext extends BehatContext
{
    public $cliroot = '';
    /**
     * Initializes context.
     * Every scenario gets it's own context object.
     *
     * @param array $parameters context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters)
    {
        $this->cliroot = dirname(dirname(__DIR__));
        
        // Initialize your context here
    }

    /** @Given /^I am in directory "([^"]*)"$/ */
    public function iAmInDirectory( $dir )
    {
      if( !file_exists('bin/terminus') ) {
        //must be in the wrong directory so chdir
        chdir($this->cliroot);
      }
      return true;

    }

      /** @When /^I run "([^"]*)"$/ */
    public function iRun($command)
    {
        // replace terminus with the path to the php
        // and pipe stderr to stdout
        $terminus_cmd = sprintf('bin/terminus', $this->cliroot );
        $command = str_replace("terminus",$terminus_cmd, $command);
        $fixture = "{$this->cliroot}/tests/fixtures/".md5($command);
        if (file_exists($fixture)) {
          $this->output = trim(file_get_contents($fixture));
          return true;
        }
        $output = shell_exec($command);
        $this->output = trim($output);
        file_put_contents($fixture,$this->output);
        chmod($fixture,0777);
    }

    /** @Then /^I should get:$/ */
    public function iShouldGet(PyStringNode $string)
    {
        if ( !preg_match( "#".preg_quote( (string) $string )."#s", $this->output ) ) {
            throw new Exception(
                "Actual output is:\n" . $this->output
            );
        }
    }

    /** @Then /^I enter "([^"]*)"$/ */
    public function iEnter( $string )
    {
      $fh = fopen( "php://stdin" , 'w' );
      fwrite( $fh, "$string\n" );
    }

//
// Place your definition and hook methods here:
//
//    /**
//     * @Given /^I have done something with "([^"]*)"$/
//     */
//    public function iHaveDoneSomethingWith($argument)
//    {
//        doSomethingWith($argument);
//    }
//
}
