<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Commands\Auth\LoginCommand;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class LoginCommandTest extends CommandTestCase
{

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();
        $this->session = $this->getMockBuilder(Session::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->session->tokens

        $this->command = new LoginCommand();
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setSession($this->session);
    }

    public function testLogIn()
    {
        $this->assertTrue(true);
    }
}
