<?php

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\InputHelper;
use Terminus\Loggers\Logger;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\Input
 */
class InputHelperTest extends PHPUnit_Framework_TestCase {

  private $inputter;

  public function __construct() {
    $command        = new ArtCommand(['runner' => new Runner()]);
    $this->inputter = new InputHelper(compact('command'));
  }

  public function testBackup() {
  }

  public function testBackupElement() {
  }

  public function testConfirm() {
  }

  public function testDay() {
    $day = $this->inputter->day(array('args' => array('day' => 'Monday')));
    $this->assertInternalType('integer', $day);
    $this->assertEquals(1, $day);
  }

  public function testEnv() {
    //From args
    $env_id = $this->inputter->env(array('args' => array('env' => 'test')));
    $this->assertInternalType('string', $env_id);
    $this->assertEquals('test', $env_id);

    //From environment variable
    $_SERVER['TERMINUS_ENV'] = 'live';
    $env_id = $this->inputter->env();
    $this->assertInternalType('string', $env_id);
    $this->assertEquals('live', $env_id);
  }

  public function testGetNullInputs() {
    $null_inputs = $this->inputter->getNullInputs();
    $this->assertInternalType('array', $null_inputs);
    $this->assertTrue(in_array('Null', $null_inputs));
  }

  public function testMenu() {
    //Single option returning value
    $only_option = $this->inputter->menu(
      array('choices' => array(5), 'return_value' => true)
    );
    $this->assertInternalType('integer', $only_option);
    $this->assertEquals(5, $only_option);

    //Single option reutrning index
    $only_option_index = $this->inputter->menu(array('choices' => array('Pick me!')));
    $this->assertInternalType('integer', $only_option_index);
    $this->assertEquals(0, $only_option_index);
  }

  public function testOptional() {
    //Finds the key
    $option = $this->inputter->optional(
      array(
        'key'     => 'key',
        'choices' => array('key' => 'value', 'not' => 'me'),
        'default' => true,
      )
    );
    $this->assertInternalType('string', $option);
    $this->assertEquals('value', $option);

    //Returns default
    $default = $this->inputter->optional(
      array(
        'key'     => 'key',
        'choices' => array('not' => 'me'),
        'default' => true,
      )
    );
    $this->assertInternalType('bool', $default);
    $this->assertEquals(true, $default);

    //Returns default from function
    $default_null = $this->inputter->optional(
      array(
        'key'     => 'key',
        'choices' => array('not' => 'me'),
      )
    );
    $this->assertEquals(null, $default_null);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  public function testOrgId() {
    //Accepting UUID
    $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
    $org  = $this->inputter->orgId(compact('args'));
    $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);

    //Accepting name
    $args = array('org' => 'Terminus Testing');
    $org  = $this->inputter->orgId(compact('args'));
    $this->assertEquals('d59379eb-0c23-429c-a7bc-ff51e0a960c2', $org);
  }

  /**
   * @vcr input_helper_org_helpers
   */
  public function testOrgName() {
    //Accepting name
    $args = array('org' => 'Terminus Testing');
    $org  = $this->inputter->orgName(compact('args'));
    $this->assertEquals('Terminus Testing', $org);

    //Accepts UUID
    $args = array('org' => 'd59379eb-0c23-429c-a7bc-ff51e0a960c2');
    $org  = $this->inputter->orgName(compact('args'));
    $this->assertEquals('Terminus Testing', $org);
  }

  public function testOrgRole() {
    //From args
    $args = ['role' => 'admin'];
    $role = $this->inputter->orgRole(compact('args'));
    $this->assertEquals('admin', $role);
  }

  public function testPhpVersion() {
    //Accepting pretty-formatted version from args
    $args    = ['version' => '5.3'];
    $version = $this->inputter->phpVersion(compact('args'));
    $this->assertEquals(53, $version);

    //Accepting API-formatted version from args
    $args    = ['version' => '55'];
    $version = $this->inputter->phpVersion(compact('args'));
    $this->assertEquals(55, $version);
  }

  public function testPrompt() {
  }

  public function testPromptSecret() {
  }

  public function testSiteRole() {
    //From args
    $args = array('role' => 'admin');
    $role = $this->inputter->siteRole(compact('args'));
    $this->assertEquals('admin', $role);
  }

  public function testServiceLevel() {
    //From args
    $args = ['args' => ['level' => 'pro']];
    $this->assertEquals('pro', $this->inputter->serviceLevel($args));

    //Customer-name service level from args
    $args = ['args' => ['level' => 'sandbox']];
    $this->assertEquals('free', $this->inputter->serviceLevel($args));
  }

  public function testSiteName() {
  }

  public function testString() {
    //Returning string
    $args   = array(
      'args'    => array('key' => 'value'),
      'key'     => 'key',
      'default' => false,
    );
    $string = $this->inputter->string($args);
    $this->assertInternalType('string', $string);
    $this->assertEquals('value', $string);
  }

  public function testUpstream() {
  }

  public function testWorkflow() {
  }

}
