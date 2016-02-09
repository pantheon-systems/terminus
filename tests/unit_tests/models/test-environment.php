<?php

use Terminus\Models\Collections\Sites;
use Terminus\Runner;

/**
 * Testing class for Terminus\Models\Environment
 */
class EnvironmentTest extends PHPUnit_Framework_TestCase {

  /**
   * @var Sites
   */
  private $sites;

  public function __construct() {
    $this->sites = new Sites(['runner' => new Runner()]);
  }

  /**
   * @vcr site_deploy
   */
  public function testCountDeployableCommits() {
    logInWithBehatCredentials();
    $site     = $this->sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $this->assertEquals(1, $test_env->countDeployableCommits());
    setDummyCredentials();
  }

  /**
   * @vcr site_deploy_no_changes
   */
  public function testCountNoDeployableCommits() {
    logInWithBehatCredentials();
    $site     = $this->sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $this->assertEquals(0, $test_env->countDeployableCommits());
    setDummyCredentials();
  }

  /**
   * @vcr site_deploy
   */
  public function testHasDeployableCode() {
    logInWithBehatCredentials();
    $site     = $this->sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $this->assertTrue($test_env->hasDeployableCode());
    setDummyCredentials();
  }

  /**
   * @vcr site_deploy_no_changes
   */
  public function testHasNoDeployableCode() {
    logInWithBehatCredentials();
    $site     = $this->sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $this->assertFalse($test_env->hasDeployableCode());
    setDummyCredentials();
  }

  /**
   * @vcr site_deploy
   */
  public function testGetParentEnvironment() {
    logInWithBehatCredentials();
    $site     = $this->sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $dev_env  = $test_env->getParentEnvironment();
    $this->assertEquals($dev_env->get('id'), 'dev');
    setDummyCredentials();
  }

}
