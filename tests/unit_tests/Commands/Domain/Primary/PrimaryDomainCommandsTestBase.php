<?php

namespace Pantheon\Terminus\UnitTests\Commands\Domain\Primary;

use Pantheon\Terminus\Commands\Domain\SetCommand;
use Pantheon\Terminus\Models\PrimaryDomain;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

abstract class PrimaryDomainCommandsTestBase extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var Session
     */
    protected $session;

    abstract protected function getSystemUnderTest();

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = $this->getSystemUnderTest();
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setSession($this->session);
        $this->command->setConfig($this->getConfig());
        $this->command->setContainer($this->getContainer());

        $this->environment->id = 'env_id';
    }

    protected function prepareTestSetReset($domain, $logTemplate, $logParams)
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $primaryDomainModel = $this->getMockBuilder(PrimaryDomain::class)
            ->disableOriginalConstructor()
            ->setMethods(['setPrimaryDomain', 'removePrimaryDomain'])
            ->getMock();

        if ($domain != null) {
            $primaryDomainModel
                ->expects($this->once())
                ->method('setPrimaryDomain')
                ->with($this->equalTo($domain))
                ->willReturn($workflow);
        } else {
            $primaryDomainModel
                ->expects($this->once())
                ->method('removePrimaryDomain')
                ->willReturn($workflow);
        }

        $this->environment->expects($this->once())
            ->method('getPrimaryDomainModel')
            ->willReturn($primaryDomainModel);

        $this->expectWorkflowProcessing();

        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($logTemplate),
                $this->equalTo($logParams)
            );
    }
}
