<?php


namespace Pantheon\Terminus\UnitTests\Commands\Domain;

use Pantheon\Terminus\Commands\Domain\SetPrimaryCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetPrimaryCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Domain\SetPrimaryCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Domain
 */
class SetPrimaryCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var Session
     */
    protected $session;

    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new SetPrimaryCommand();
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setSession($this->session);
        $this->command->setConfig($this->getConfig());
        $this->command->setContainer($this->getContainer());

        $this->environment->id = 'env_id';
    }

    public function testSetResetProvider()
    {
        return [
            ['some.domain', ],
            [null, ],
        ];
    }

    public function testSet()
    {
        $site_name = 'site_name';
        $domain = 'some.domain';
        $this->prepareTestSetReset($domain, 'Setting primary domain to {domain}...', ['domain' => $domain]);

        $out = $this->command->set("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }

    public function testReset()
    {
        $site_name = 'site_name';
        $domain = null;
        $this->prepareTestSetReset($domain, 'Unsetting primary domain...', []);

        $out = $this->command->reset("$site_name.{$this->environment->id}", $domain);
        $this->assertNull($out);
    }

    protected function prepareTestSetReset($domain, $logTemplate, $logParams)
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->environment->expects($this->once())
            ->method('setPrimaryDomain')
            ->with($this->equalTo($domain))
            ->willReturn($workflow);

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
