<?php

namespace Pantheon\Terminus\Tests\Unit;

use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Site;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use PHPUnit\Framework\TestCase;
use Consolidation\Config\Config;
use League\Container\Container;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkflowProcessingTraitTest extends TestCase
{
    private function createTraitUser(array $overrides = []): object
    {
        $input = $this->createMock(InputInterface::class);
        $input->method('isInteractive')->willReturn($overrides['interactive'] ?? false);

        $config = new Config($overrides['config'] ?? ['workflow_polling_delay_ms' => 1]);
        $logger = $this->createMock(LoggerInterface::class);

        $siteProvider = $overrides['getSiteById'] ?? null;

        return new class ($input, $config, $logger, $siteProvider) {
            use WorkflowProcessingTrait;

            private $input;
            private $config;
            private $logger;
            private $siteProvider;

            public function __construct($input, $config, $logger, $siteProvider)
            {
                $this->input = $input;
                $this->config = $config;
                $this->logger = $logger;
                $this->siteProvider = $siteProvider;
            }

            public function input()
            {
                return $this->input;
            }

            public function output()
            {
                return null;
            }

            public function getConfig()
            {
                return $this->config;
            }

            public function getContainer()
            {
                return new \League\Container\Container();
            }

            public function log()
            {
                return $this->logger;
            }

            public function getSiteById(string $id)
            {
                return ($this->siteProvider)($id);
            }

            public function callWaitForWorkflow(
                int $start_time,
                Site $site,
                string $env_name,
                string $expected_workflow_description = '',
                int $max_wait_in_seconds = 180,
                int $max_not_found_attempts = 0
            ) {
                return $this->waitForWorkflow(
                    $start_time,
                    $site,
                    $env_name,
                    $expected_workflow_description,
                    $max_wait_in_seconds,
                    $max_not_found_attempts
                );
            }
        };
    }

    private function createWorkflowMock(
        bool $finished = true,
        bool $successful = true,
        string $message = 'Done',
        array $attributes = []
    ): Workflow {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isFinished')->willReturn($finished);
        $workflow->method('isSuccessful')->willReturn($successful);
        $workflow->method('getMessage')->willReturn($message);
        $workflow->method('fetch')->willReturnSelf();
        $workflow->method('getStatus')->willReturn($successful ? 'succeeded' : 'failed');
        $workflow->method('get')->willReturnCallback(function ($key) use ($attributes) {
            return $attributes[$key] ?? null;
        });
        return $workflow;
    }

    public function testProcessWorkflowSuccessNoTimeout(): void
    {
        $workflow = $this->createWorkflowMock(true, true);
        $user = $this->createTraitUser();

        $result = $user->processWorkflow($workflow);
        $this->assertSame($workflow, $result);
    }

    public function testProcessWorkflowSuccessWithTimeout(): void
    {
        $workflow = $this->createWorkflowMock(true, true);
        $user = $this->createTraitUser();

        $result = $user->processWorkflow($workflow, 60);
        $this->assertSame($workflow, $result);
    }

    public function testProcessWorkflowFailureThrowsException(): void
    {
        $workflow = $this->createWorkflowMock(true, false, 'Deploy failed');
        $user = $this->createTraitUser();

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('Deploy failed');
        $user->processWorkflow($workflow);
    }

    public function testProcessWorkflowTimeoutThrowsException(): void
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->method('isFinished')->willReturn(false);
        $workflow->method('fetch')->willReturnSelf();

        $user = $this->createTraitUser();

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessageMatches('/timed out/');
        $user->processWorkflow($workflow, 1);
    }

    public function testProcessWorkflowRespectsMinPollingInterval(): void
    {
        $workflow = $this->createWorkflowMock(true, true);
        $user = $this->createTraitUser(['config' => ['workflow_polling_delay_ms' => 500]]);

        $start = microtime(true);
        $user->processWorkflow($workflow);
        $elapsed = microtime(true) - $start;

        // With polling interval below 1000ms, it gets clamped to 1000ms.
        // But since the workflow finishes immediately on first fetch,
        // it should still complete in well under 2 seconds.
        $this->assertLessThan(2.0, $elapsed);
    }

    public function testWaitForWorkflowFindsMatchingWorkflow(): void
    {
        $matchingWorkflow = $this->createMock(Workflow::class);
        $matchingWorkflow->method('get')->willReturnCallback(function ($key) {
            return match ($key) {
                'created_at' => time() + 10,
                'description' => 'Sync code on dev',
                default => null,
            };
        });
        $matchingWorkflow->method('fetch')->willReturnSelf();
        $matchingWorkflow->method('getStatus')->willReturn('succeeded');
        $matchingWorkflow->method('isFinished')->willReturn(true);
        $matchingWorkflow->method('isSuccessful')->willReturn(true);

        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetch')->willReturnSelf();
        $workflows->method('all')->willReturn([$matchingWorkflow]);

        $site = $this->createMock(Site::class);
        $site->id = 'site-123';
        $site->method('getWorkflows')->willReturn($workflows);

        $user = $this->createTraitUser([
            'getSiteById' => function () use ($site) {
                return $site;
            },
        ]);

        // Should not throw — workflow found and processed successfully.
        $user->callWaitForWorkflow(
            time(),
            $site,
            'dev',
            'Sync code on dev',
            180,
            0
        );

        $this->assertTrue(true);
    }

    public function testWaitForWorkflowUsesDefaultDescription(): void
    {
        $matchingWorkflow = $this->createMock(Workflow::class);
        $matchingWorkflow->method('get')->willReturnCallback(function ($key) {
            return match ($key) {
                'created_at' => time() + 10,
                'description' => 'Sync code on multidev',
                default => null,
            };
        });
        $matchingWorkflow->method('fetch')->willReturnSelf();
        $matchingWorkflow->method('getStatus')->willReturn('succeeded');
        $matchingWorkflow->method('isFinished')->willReturn(true);
        $matchingWorkflow->method('isSuccessful')->willReturn(true);

        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetch')->willReturnSelf();
        $workflows->method('all')->willReturn([$matchingWorkflow]);

        $site = $this->createMock(Site::class);
        $site->id = 'site-123';
        $site->method('getWorkflows')->willReturn($workflows);

        $user = $this->createTraitUser([
            'getSiteById' => function () use ($site) {
                return $site;
            },
        ]);

        // Empty description should default to "Sync code on multidev".
        $user->callWaitForWorkflow(
            time(),
            $site,
            'multidev',
            '',
            180,
            0
        );

        $this->assertTrue(true);
    }

    public function testWaitForWorkflowMaxNotFoundAttemptsThrowsException(): void
    {
        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetch')->willReturnSelf();
        $workflows->method('all')->willReturn([]);

        $site = $this->createMock(Site::class);
        $site->id = 'site-123';
        $site->method('getWorkflows')->willReturn($workflows);

        $user = $this->createTraitUser([
            'getSiteById' => function () use ($site) {
                return $site;
            },
        ]);

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessageMatches('/Attempted/');
        $user->callWaitForWorkflow(
            time(),
            $site,
            'dev',
            'Sync code on dev',
            180,
            3
        );
    }

    public function testWaitForWorkflowTimeoutThrowsException(): void
    {
        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetch')->willReturnSelf();
        $workflows->method('all')->willReturn([]);

        $site = $this->createMock(Site::class);
        $site->id = 'site-123';
        $site->method('getWorkflows')->willReturn($workflows);

        $user = $this->createTraitUser([
            'getSiteById' => function () use ($site) {
                return $site;
            },
        ]);

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessageMatches('/timed out/');
        $user->callWaitForWorkflow(
            time(),
            $site,
            'dev',
            'Sync code on dev',
            1,
            0
        );
    }

    public function testWaitForWorkflowSkipsOlderWorkflows(): void
    {
        $start_time = time();

        $oldWorkflow = $this->createMock(Workflow::class);
        $oldWorkflow->method('get')->willReturnCallback(function ($key) use ($start_time) {
            return match ($key) {
                'created_at' => $start_time - 100,
                'description' => 'Sync code on dev',
                default => null,
            };
        });

        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetch')->willReturnSelf();
        $workflows->method('all')->willReturn([$oldWorkflow]);

        $site = $this->createMock(Site::class);
        $site->id = 'site-123';
        $site->method('getWorkflows')->willReturn($workflows);

        $user = $this->createTraitUser([
            'getSiteById' => function () use ($site) {
                return $site;
            },
        ]);

        // Old workflow should be skipped, then max_not_found_attempts hit.
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessageMatches('/Attempted/');
        $user->callWaitForWorkflow(
            $start_time,
            $site,
            'dev',
            'Sync code on dev',
            180,
            2
        );
    }
}
