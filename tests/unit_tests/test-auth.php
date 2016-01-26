<?php

use Terminus\Auth;
use Terminus\TokensCache;
use Terminus\Exceptions\TerminusException;

/**
 * Testing class for Terminus\Auth
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
    $auth = new Auth();
    $this->assertTrue(strpos(get_class($auth), 'Auth') !== false);
  }

  public function testEnsureLogin() {
    $this->assertTrue(Auth::ensureLogin());
  }

  public function testGetMachineTokenCreationUrl() {
    $url = Auth::getMachineTokenCreationUrl();
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
    $file_name = '/tmp/output';
    setOutputDestination($file_name);
    $opts = $this->auth->logInViaMachineToken(getBehatCredentials());
    $this->assertEquals(print_r($opts, true), 'hi');
    $output = retrieveOutput();
    $this->assertTrue(
      strpos($output, 'Logging in via machine token') !== false
    );
    resetOutputDestination($file_name); 
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
