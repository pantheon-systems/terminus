<?php
namespace Pantheon\Terminus\UnitTests\Commands\Site\Team;

use Pantheon\Terminus\UnitTests\Commands\Site\Team\TeamCommandTest;
use Pantheon\Terminus\Commands\Site\Team\ListCommand;

/**
 * Testing class for Pantheon\Terminus\Commands\Site\Team\ListCommand
 */
class ListCommandTest extends TeamCommandTest
{
    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new ListCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Tests the site:team:list command.
     */
    public function testListCommand()
    {
        $user = new \stdClass();
        $user->id = 'abcdef';
        $user->profile = new \stdClass();
        $user->profile->firstname = 'Daisy';
        $user->profile->lastname = 'Duck';
        $user->email = 'daisy@duck.com';

        $this->user_membership->expects($this->any())
            ->method('get')
            ->will($this->onConsecutiveCalls($user, 'team_member', $user, 'team_member'));

        $this->site->user_memberships->expects($this->once())
            ->method('all')
            ->willReturn([$this->user_membership, $this->user_membership]);

        $out = $this->command->teamList('mysite');
        foreach ($out as $u) {
            $this->assertEquals($u['first'], $user->profile->firstname);
            $this->assertEquals($u['last'], $user->profile->lastname);
            $this->assertEquals($u['email'], $user->email);
            $this->assertEquals($u['role'], 'team_member');
            $this->assertEquals($u['uuid'], $user->id);
        }
    }
}
