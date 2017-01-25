<?php

namespace Pantheon\Terminus\UnitTests\Hooks;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Hooks\Authorizer;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Session\Session;

/**
 * Class AuthorizerTest
 * Testing class for Pantheon\Terminus\Hooks\Authorizer
 * @package Pantheon\Terminus\UnitTests\Hooks
 */
class AuthorizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Authorizer
     */
    protected $authorizer;
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var SavedToken
     */
    protected $token;
    /**
     * @var SavedTokens
     */
    protected $tokens;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->token = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tokens = $this->getMockBuilder(SavedTokens::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->authorizer = new Authorizer();
        $this->authorizer->setConfig($this->config);
        $this->authorizer->setSession($this->session);
    }

    /**
     * Tests the Authorizer::ensureLogin() function when the user is logged in
     */
    public function testEnsureLoginLoggedIn()
    {
        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(true);
        $this->session->expects($this->never())
            ->method('getTokens');
        $this->tokens->expects($this->never())
            ->method('all');
        $this->tokens->expects($this->never())
            ->method('get');
        $this->config->expects($this->never())
            ->method('get');
        $this->token->expects($this->never())
            ->method('logIn');

        $this->assertNull($this->authorizer->ensureLogin());
    }

    /**
     * Tests the Authorizer::ensureLogin() function when the user has one saved token and is not logged in
     */
    public function testEnsureLoginWithOneSavedToken()
    {
        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(false);
        $this->session->expects($this->once())
            ->method('getTokens')
            ->with()
            ->willReturn($this->tokens);
        $this->tokens->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->token,]);
        $this->config->expects($this->never())
            ->method('get');
        $this->tokens->expects($this->never())
            ->method('get');
        $this->token->expects($this->once())
            ->method('logIn')
            ->with();

        $this->assertNull($this->authorizer->ensureLogin());
    }

    /**
     * Tests the Authorizer::ensureLogin() function when the user has several saved tokens and the 'user' config
     * setting has been set and is not logged in
     */
    public function testEnsureLoginWithUserConfigSetting()
    {
        $email = 'handle@domain.ext';
        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(false);
        $this->session->expects($this->once())
            ->method('getTokens')
            ->with()
            ->willReturn($this->tokens);
        $this->tokens->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$this->token, $this->token,]);
        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('user'))
            ->willReturn($email);
        $this->tokens->expects($this->once())
            ->method('get')
            ->with()
            ->willReturn($this->token);
        $this->token->expects($this->once())
            ->method('logIn')
            ->with();

        $this->assertNull($this->authorizer->ensureLogin());
    }

    /**
     * Tests the Authorizer::ensureLogin() function when the user has no saved tokens and is not logged in
     */
    public function testEnsureLoginNoTokens()
    {
        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(false);
        $this->session->expects($this->once())
            ->method('getTokens')
            ->with()
            ->willReturn($this->tokens);
        $this->tokens->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([]);
        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('user'))
            ->willReturn('');
        $this->tokens->expects($this->never())
            ->method('get');
        $this->token->expects($this->never())
            ->method('logIn');

        $this->setExpectedException(
            TerminusException::class,
            'You are not logged in. Run `auth:login` to authenticate or `help auth:login` for more info.'
        );

        $this->assertNull($this->authorizer->ensureLogin());
    }
}
