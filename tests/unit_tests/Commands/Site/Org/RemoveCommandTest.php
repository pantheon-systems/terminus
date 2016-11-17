<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Org;

use Pantheon\Terminus\Commands\Site\Org\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\Org\Site\OrgSiteCommandTest;
use Terminus\Collections\SiteOrganizationMemberships;
use Terminus\Models\SiteOrganizationMembership;

class RemoveCommandTest extends OrgSiteCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->site->org_memberships = $this->getMockBuilder(SiteOrganizationMemberships::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->organization->expects($this->any())
            ->method('getName')
            ->willReturn('org_id');

        $this->site->expects($this->any())
            ->method('getName')
            ->willReturn('my-site');

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    public function testRemoveOrg()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        // workflow succeeded
        $workflow->expects($this->once())->method('checkProgress')->willReturn(true);
        $workflow->expects($this->once())->method('getMessage')->willReturn('successful workflow');

        $membership = $this->getMockBuilder(SiteOrganizationMembership::class)
            ->disableOriginalConstructor()
            ->getMock();
        $membership->expects($this->once())
            ->method('delete')
            ->willReturn($workflow);

        $this->site->org_memberships->expects($this->once())
            ->method('get')
            ->with('org_id')
            ->willReturn($membership);

        $this->logger->expects($this->at(0))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Removing {org} as a supporting organization from {site}.'),
                $this->equalTo(['site' => 'my-site', 'org' => 'org_id'])
            );
        $this->logger->expects($this->at(1))
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('successful workflow')
            );
        $this->command->removeOrgFromSite('my-site', 'org_id');
    }
}
