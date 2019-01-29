<?php

namespace Pantheon\Terminus\UnitTests\Commands\Owner;

use Pantheon\Terminus\Commands\Owner\SetCommand;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\Collections\SiteUserMemberships;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\SiteUserMembership;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\Owner\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Owner
 */
class SetCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var SiteUserMemberships
     */
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
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
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
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $user->id = 'user_id';
        $user_membership->method('getUser')->willReturn($user);

        $this->user_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($email))
            ->willReturn($user_membership);

        $this->site->expects($this->once())
            ->method('setOwner')
            ->with($this->equalTo($user->id))
            ->willReturn($workflow);

        $this->site->expects($this->once())
            ->method('getName')
            ->willReturn($site_name);
        $user->expects($this->once())
            ->method('getName')
            ->willReturn($full_name);

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
