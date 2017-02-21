<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Org;

use Pantheon\Terminus\Commands\Site\Org\AddCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\Org\Site\OrgSiteCommandTest;
use Pantheon\Terminus\Collections\SiteOrganizationMemberships;

/**
 * Class AddCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Org\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Org
 */
class AddCommandTest extends OrgSiteCommandTest
{
    protected $org_memberships;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->method('getOrganizationMemberships')->willReturn($this->org_memberships);

        $this->organization->expects($this->any())
            ->method('getName')
            ->willReturn('org_id');

        $this->site->expects($this->any())
            ->method('getName')
            ->willReturn('my-site');

        $this->command = new AddCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    public function testAdd()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $this->org_memberships->expects($this->once())
            ->method('create')
            ->with($this->organization, 'team_member')
            ->willReturn($workflow);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Adding {org} as a supporting organization to {site}.'),
                $this->equalTo(['site' => 'my-site', 'org' => 'org_id',])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );
        $this->command->add('my-site', 'org_id');
    }
}
