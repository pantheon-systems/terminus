<?php

use Terminus\Caches\TokensCache;
use Terminus\Exceptions\TerminusException;
use Terminus\Models\Auth;
use Terminus\Runner;

/**
 * Testing class for Terminus\Models\Auth
 */
class AuthTest extends PHPUnit_Framework_TestCase {

  /**
   * @var Auth
   */
  private $auth;

  public function __construct() {
    $this->auth = new Auth();
  }

  public function testConstruct() {
    $this->assertTrue(strpos(get_class($this->auth), 'Auth') !== false);
  }

  public function testGetAllSavedTokenEmails() {
    $tokens_cache = new TokensCache();
    $tokens_dir   = $tokens_cache->getCacheDir();
    $token_count  = count(scandir($tokens_dir)) - 2;
    $tokens       = $this->auth->getAllSavedTokenEmails();
    $this->assertEquals(count($tokens), $token_count);
    $this->assertInternalType('array', $tokens);
  }

  public function testGetMachineTokenCreationUrl() {
    $url = $this->auth->getMachineTokenCreationUrl();
    $this->assertInternalType('string', $url);
    $this->assertInternalType('integer', strpos($url, 'machine-token/create'));
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
   * @expectedExceptionMessage Login unsuccessful
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
