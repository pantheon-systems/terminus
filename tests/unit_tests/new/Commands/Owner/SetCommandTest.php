<?php

namespace Pantheon\Terminus\UnitTests\Commands\Owner;

use Pantheon\Terminus\Commands\Owner\SetCommand;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\Workflow;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Owner\SetCommand
 */
class SetCommandTest extends CommandTestCase
{
    protected $user_memberships;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        parent::setUp();

        $this->user_memberships = $this->getMockBuilder(SiteUserMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->method('getUserMemberships')->willReturn($this->user_memberships);

        $this->command = new SetCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    /**
     * Exercises owner:set when the proposed owner is a team member
     */
    public function testOwnerSetValidOwner()
    {
        $site_name = 'site_name';
        $email = 'a-valid-email';
        $full_name = 'Dev User';

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_membership = $this->getMockBuilder(SiteUserMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_membership->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user_membership->user->id = 'user_id';

        $this->user_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->willReturn($user_membership);

        $this->site->expects($this->once())
            ->method('setOwner')
            ->with($this->equalTo($user_membership->user->id))
            ->willReturn($workflow);

        $workflow->expects($this->once())
            ->method('wait')
            ->with()
            ->willReturn(true);

        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $user_membership->user->expects($this->once())
            ->method('get')
            ->with($this->equalTo('profile'))
            ->willReturn((object)compact('full_name'));

        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Promoted {user} to owner of {site}'),
                $this->equalTo(['user' => $full_name, 'site' => $site_name,])
            );

        $out = $this->command->setOwner($site_name, $email);
        $this->assertNull($out);
    }

    /**
     * Exercises owner:set when the proposed owner is not a team member
     *
     * @expectedExceptionMessage The new owner must be added with "terminus site:team:add" before promoting.
     */
    public function testOwnerSetInvalidOwner()
    {
        $email = 'a-valid-email';

        $this->user_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->will($this->throwException(new TerminusNotFoundException));

        $this->site->expects($this->never())
            ->method('setOwner');
        $this->logger->expects($this->never())
            ->method('log');

        $this->setExpectedException(TerminusNotFoundException::class);

        $out = $this->command->setOwner('dummy-site', $email);
        $this->assertNull($out);
    }


    /**
     * Exercises owner:set when throwing an error that is not a TerminusNotFoundException
     */
    public function testOwnerSetDontCatchException()
    {
        $email = 'a-valid-email';

        $this->user_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->will($this->throwException(new \Exception));

        $this->site->expects($this->never())
            ->method('setOwner');
        $this->logger->expects($this->never())
            ->method('log');

        $this->setExpectedException(\Exception::class);

        $out = $this->command->setOwner('dummy-site', $email);
        $this->assertNull($out);
    }
}
