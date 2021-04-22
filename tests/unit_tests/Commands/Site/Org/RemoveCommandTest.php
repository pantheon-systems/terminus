<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Org;

use Pantheon\Terminus\Commands\Site\Org\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\Org\Site\OrgSiteCommandTest;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;
use Pantheon\Terminus\Models\SiteOrganizationMembership;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class RemoveCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Org\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Org
 */
class RemoveCommandTest extends OrgSiteCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @var SiteOrganizationMemberships
     */
    protected $org_memberships;
    /**
     * @var string
     */
    protected $site_name;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site_name = 'site name';

        $this->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->expects($this->once())
            ->method('getOrganizationMemberships')
            ->with()
            ->willReturn($this->org_memberships);
        $this->organization->expects($this->once())
            ->method('getName')
            ->willReturn($this->organization->id);
        $this->site->expects($this->once())
            ->method('getName')
            ->willReturn($this->site_name);

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the site:org:remove command
     */
    public function testRemove()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        // workflow succeeded
        $workflow->expects($this->once())
            ->method('getMessage')
            ->willReturn('successful workflow');

        $membership = $this->getMockBuilder(SiteOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $membership->expects($this->once())
            ->method('delete')
            ->with()
            ->willReturn($workflow);

        $this->org_memberships->expects($this->once())
            ->method('get')
            ->with($this->equalTo($this->organization->id))
            ->willReturn($membership);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Removing {org} as a supporting organization from {site}.'),
                $this->equalTo(['site' => $this->site_name, 'org' => $this->organization->id,])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );

        $out = $this->command->remove($this->site_name, $this->organization->id);
        $this->assertNull($out);
    }
}
