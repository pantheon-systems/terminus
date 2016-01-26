<?php

use Terminus\Models\Collections\Sites;

/**
 * Testing class for Terminus\Models\Environment
 */
class EnvironmentTest extends PHPUnit_Framework_TestCase {

  /**
   * @vcr site_deploy
   */
  public function testHasDeployableCode() {
    logInWithBehatCredentials();
    $sites    = new Sites();
    $site     = $sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $this->assertTrue($test_env->hasDeployableCode());
    setDummyCredentials();
  }

  /**
   * @vcr site_deploy
   */
  public function testGetParentEnvironment() {
    logInWithBehatCredentials();
    $sites    = new Sites();
    $site     = $sites->get('behat-tests');
    $test_env = $site->environments->get('test');
    $dev_env  = $test_env->getParentEnvironment();
    $this->assertEquals($dev_env->get('id'), 'dev');
    setDummyCredentials();
  }

}
