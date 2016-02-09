<?php

use Terminus\Caches\TokensCache;
use Terminus\Commands\ArtCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Helpers\AuthHelper;
use Terminus\Loggers\Logger;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\AuthHelper
 */
class AuthTest extends PHPUnit_Framework_TestCase {

  /**
   * @var AuthHelper
   */
  private $auth;

  public function __construct() {
    $command    = new ArtCommand(['runner' => new Runner()]);
    $this->auth = new AuthHelper(compact('command'));
  }

  public function testConstruct() {
    $this->assertTrue(strpos(get_class($this->auth), 'AuthHelper') !== false);
  }

  public function testEnsureLogin() {
    $this->assertTrue($this->auth->ensureLogin());
  }

  public function testGetMachineTokenCreationUrl() {
    $url = $this->auth->getMachineTokenCreationUrl();
    $this->assertInternalType('string', $url);
    $this->assertInternalType('integer', strpos($url, 'machine-token/create'));
  }

  public function testGetOnlySavedToken() {
    $tokens_cache = new TokensCache();
    $tokens_dir   = $tokens_cache->getCacheDir();
    $token_count  = count(scandir($tokens_dir)) - 2;
    $only_token   = $this->auth->getOnlySavedToken();
    if ($token_count != 1) {
      $this->assertFalse($only_token);
    } else {
      $this->assertInternalType('array', $only_token);
    }
  }

  public function testLoggedIn() {
    $this->assertTrue($this->auth->loggedIn());
  }

  /**
   * @vcr auth_login_machine-token
   */
  public function testLogInViaMachineToken() {
    $passed = $this->auth->logInViaMachineToken(getBehatCredentials());
    $this->assertTrue($passed);
    setDummyCredentials();
  }

  /**
   * @expectedException        \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage Authorization failed
   */
  public function testLogInViaUsernameAndPassword() {
    $creds = getBehatCredentials();
    $this->assertTrue(
      $this->auth->logInViaUsernameAndPassword(
        $creds['username'],
        $creds['password']
      )
    );

    $this->auth->logInViaUsernameAndPassword('invalid', 'password');
    setDummyCredentials();
  }

  public function testTokenExistsForEmail() {
    $tokens_cache = new TokensCache();
    $tokens_dir   = $tokens_cache->getCacheDir();
    $creds        = getBehatCredentials();
    $file_name    = $tokens_dir . '/' . $creds['username'];
    setOutputDestination($file_name);
    $this->assertTrue($this->auth->tokenExistsForEmail($creds['username']));
    resetOutputDestination($file_name);
    $this->assertFalse($this->auth->tokenExistsForEmail('invalid'));
  }

}
