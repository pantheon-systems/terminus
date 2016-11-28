<?php

namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\Auth\WhoamiCommand;
use Pantheon\Terminus\Models\User;

/**
 * Class WhoamiCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Auth\WhoamiCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Auth
 */
class WhoamiCommandTest extends AuthTest
{

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new WhoamiCommand();
        $this->command->setConfig($this->config);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the auth:whoami command When the user is logged in
     */
    public function testWhoAmI()
    {
        $email = "email@ddr.ess";
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($user);
        $user->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn(compact('email'));

        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(true);
        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($user);
        $this->logger->expects($this->never())
            ->method('log');

        $out = $this->command->whoAmI();
        $this->assertInstanceOf(PropertyList::class, $out);
    }

    /**
     * Tests the auth:whoami command When the user is logged out
     */
    public function testWhoAmIWhenIAmLoggedOut()
    {
        $this->session->expects($this->once())
            ->method('isActive')
            ->with()
            ->willReturn(false);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('You are not logged in.')
            );

        $out = $this->command->whoAmI();
        $this->assertNull($out);
    }
}
