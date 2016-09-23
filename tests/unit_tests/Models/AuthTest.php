<?php

namespace Terminus\UnitTests\Models;

use Terminus\Caches\TokensCache;
use Terminus\Models\Auth;
use Terminus\UnitTests\TerminusTest;

/**
 * Testing class for Terminus\Models\Auth
 */
class AuthTest extends TerminusTest
{

  /**
   * @var Auth
   */
    private $auth;

    public function setUp()
    {
        parent::setUp();
        $this->auth = new Auth();
    }

    public function testConstruct()
    {
        $this->assertTrue(strpos(get_class($this->auth), 'Auth') !== false);
    }

    public function testGetAllSavedTokenEmails()
    {
        $tokens_cache = new TokensCache();
        $tokens_dir   = $tokens_cache->getCacheDir();
        $token_count  = count(scandir($tokens_dir)) - 2;
        $tokens       = $this->auth->getAllSavedTokenEmails();
        $this->assertEquals(count($tokens), $token_count);
        $this->assertInternalType('array', $tokens);
    }

    public function testGetMachineTokenCreationUrl()
    {
        $url = $this->auth->getMachineTokenCreationUrl();
        $this->assertInternalType('string', $url);
        $this->assertInternalType('integer', strpos($url, 'machine-token/create'));
    }

    /**
     * @vcr auth_login
     */
    public function testLoggedIn()
    {
        $this->logInWithVCRCredentials();
        $this->assertTrue($this->auth->loggedIn());
    }

  /**
   * @vcr auth_login
   */
    public function testLogInViaMachineToken()
    {
        $creds = $this->getVCRCredentials();
        $creds['token'] = $creds['machine_token'];
        $passed = $this->auth->logInViaMachineToken($creds);
        $this->assertTrue($passed);
        $this->setDummyCredentials();
    }

  /**
   * @expectedException        \Terminus\Exceptions\TerminusException
   * @expectedExceptionMessage Login unsuccessful for devuser@pantheon.io
   */
    public function testLogInViaUsernameAndPassword()
    {
        $creds = $this->getVCRCredentials();
        $this->assertTrue(
            $this->auth->logInViaUsernameAndPassword(
                $creds['username'],
                $creds['password']
            )
        );

        $this->auth->logInViaUsernameAndPassword('invalid', 'password');
        $this->setDummyCredentials();
    }

    public function testTokenExistsForEmail()
    {
        $tokens_cache = new TokensCache();
        $tokens_dir   = $tokens_cache->getCacheDir();
        $creds        = $this->getVCRCredentials();
        $file_name    = $tokens_dir . '/' . $creds['username'];
        $this->setOutputDestination($file_name);
        $this->assertTrue($this->auth->tokenExistsForEmail($creds['username']));
        $this->resetOutputDestination($file_name);
        $this->assertFalse($this->auth->tokenExistsForEmail('invalid'));
    }
}
