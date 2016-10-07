<?php


namespace Pantheon\Terminus\UnitTests\Commands\Auth;

use Pantheon\Terminus\Commands\Auth\WhoamiCommand;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Terminus\Models\User;

class WhoamiCommandTest extends CommandTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->command = new WhoamiCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    public function testWhoamiLoggedIn()
    {
        $info = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'id' => '111111111111111111111111111111111111111111111',
        ];

        $this->user->expects($this->once())
            ->method('fetch')
            ->willReturn($this->user);

        $this->user->expects($this->once())
            ->method('serialize')
            ->willReturn($info);

        $out = $this->command->whoAmI();
        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\AssociativeList', $out);
        $this->assertEquals($info, $out->getArrayCopy());
    }

    public function testWhoamiLoggedOut()
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('You are not logged in.')
            );

        $this->command->setSession($this->session);
        $out = $this->command->whoAmI();
        $this->assertNull($out);
    }
}
