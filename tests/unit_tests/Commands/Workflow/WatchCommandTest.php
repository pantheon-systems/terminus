<?php

namespace Pantheon\Terminus\UnitTests\Commands\Workflow;

use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Commands\Workflow\WatchCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\WorkflowOperation;

/**
 * Class WatchCommandTest
 * Testing class for Pantheon\Terminus\Commands\Workflow\WatchCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Workflow
 */
class WatchCommandTest extends WorkflowCommandTest
{
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var WorkflowOperation
     */
    protected $operation;
    /**
     * @var Workflow
     */
    protected $workflow;
    /**
     * @var Workflows
     */
    protected $workflows;

    const ARBITRARY_TIMESTAMP = -14160840;

    /**
     * Setup the test fixture.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(TerminusConfig::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['get', 'wasFinishedBefore', 'wasCreatedBefore', 'fetchWithLogs', 'operations',])
            ->disableOriginalConstructor()
            ->getMock();
        $this->operation = $this->getMockBuilder(WorkflowOperation::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->expects($this->once())
            ->method('get')
            ->willReturn($this->site);
        $this->config->expects($this->exactly(2))
            ->method('get')
            ->with($this->equalTo('date_format'))
            ->willReturn('Y-m-d H:i:s');
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Watching workflows...')
            );
        $this->site->expects($this->any())
            ->method('getWorkflows')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->any())
            ->method('fetchWithOperations')
            ->with()
            ->willReturn($this->workflows);
        $this->workflows->expects($this->any())
            ->method('all')
            ->with()
            ->willReturn([$this->workflow,]);
        $this->workflows->expects($this->any())
            ->method('lastCreatedAt')
            ->with()
            ->willReturn(self::ARBITRARY_TIMESTAMP);
        $this->workflows->expects($this->any())
            ->method('lastFinishedAt')
            ->with()
            ->willReturn(self::ARBITRARY_TIMESTAMP);

        $this->command = new WatchCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->command->setConfig($this->config);
    }

    /**
     * Tests the workflow:list command
     */
    public function testWatch()
    {
        $this->environment->id = 'dev';
        $this->workflow->id = 'workflow id';
        $settings = [
            'description' => 'description',
            'started_at' => self::ARBITRARY_TIMESTAMP,
            'finished_at' => self::ARBITRARY_TIMESTAMP,
        ];
        $this->startedNoticeExpectations($settings);
        $this->finishedNoticeExpectations($settings);
        $this->operationLogsExpectations($settings);

        $out = $this->command->watch('site name', ['checks' => 0,]);
        $this->assertNull($out);
    }

    /**
     * Tests the workflow:list command's emitting of the workflow-started notice
     *
     * @param array $settings
     */
    protected function startedNoticeExpectations(array $settings = [])
    {
        $description = $settings['description'];
        $started_at = $settings['started_at'];

        $this->workflow->expects($this->at(0))
            ->method('get')
            ->with($this->equalTo('created_at'))
            ->willReturn($started_at + 100);
        $this->workflow->expects($this->at(1))
            ->method('get')
            ->with($this->equalTo('description'))
            ->willReturn($description);
        $this->workflow->expects($this->at(2))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->environment->id);
        $this->workflow->expects($this->at(3))
            ->method('get')
            ->with($this->equalTo('started_at'))
            ->willReturn($started_at);
        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Started {id} {description} ({env}) at {time}'),
                $this->equalTo([
                    'id' => $this->workflow->id,
                    'description' => $description,
                    'env' => $this->environment->id,
                    'time' => '1969-07-21 02:26:00',
                ])
            );
    }

    /**
     * Tests the workflow:list command's emitting of the workflow-finished notice
     *
     * @param array $settings
     */
    protected function finishedNoticeExpectations(array $settings = [])
    {
        $description = $settings['description'];
        $finished_at = $settings['finished_at'];

        $this->workflow->expects($this->at(4))
            ->method('get')
            ->with($this->equalTo('finished_at'))
            ->willReturn($finished_at + 100);
        $this->workflow->expects($this->at(5))
            ->method('get')
            ->with($this->equalTo('description'))
            ->willReturn($description);
        $this->workflow->expects($this->at(6))
            ->method('get')
            ->with($this->equalTo('environment'))
            ->willReturn($this->environment->id);
        $this->workflow->expects($this->at(7))
            ->method('get')
            ->with($this->equalTo('finished_at'))
            ->willReturn($finished_at);
        $this->logger->expects($this->at(2))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Finished workflow {id} {description} ({env}) at {time}'),
                $this->equalTo([
                    'id' => $this->workflow->id,
                    'description' => $description,
                    'env' => $this->environment->id,
                    'time' => '1969-07-21 02:26:00',
                ])
            );
    }

    /**
     * Tests the workflow:list command's emitting of workflow operation notices
     *
     * @param array $settings
     */
    protected function operationLogsExpectations(array $settings = [])
    {
        $description = $settings['description'];

        $this->workflow->expects($this->at(8))
            ->method('get')
            ->with($this->equalTo('has_operation_log_output'))
            ->willReturn(true);
        $this->workflow->expects($this->once())
            ->method('fetchWithLogs')
            ->with()
            ->willReturn($this->workflow);
        $this->workflow->expects($this->once())
            ->method('operations')
            ->with()
            ->willReturn([$this->operation,]);
        $this->operation->expects($this->once())
            ->method('has')
            ->with($this->equalTo('log_output'))
            ->willReturn(true);
        $this->operation->expects($this->any())
            ->method('__toString')
            ->with()
            ->willReturn($description);
        $this->logger->expects($this->at(3))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo($description)
            );
    }
}
