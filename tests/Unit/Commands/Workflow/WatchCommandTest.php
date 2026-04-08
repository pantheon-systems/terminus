<?php

namespace Pantheon\Terminus\Tests\Unit\Commands\Workflow;

use Pantheon\Terminus\Commands\Workflow\WatchCommand;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Site;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Consolidation\Config\Config;

class WatchCommandTest extends TestCase
{
    /**
     * Build a WatchCommand with sleep() stubbed out, logging captured,
     * and the workflow fetch cycle controlled by a callback.
     *
     * @param array &$sleepCalls   Populated with each interval passed to sleep().
     * @param array &$logWarnings  Populated with each warning message logged.
     *
     * @return WatchCommand
     */
    private function buildCommand(array &$sleepCalls, array &$logWarnings): WatchCommand
    {
        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetchWithOperations')->willReturnSelf();
        $workflows->method('setData')->willReturnSelf();
        $workflows->method('lastCreatedAt')->willReturn(0);
        $workflows->method('lastFinishedAt')->willReturn(0);
        $workflows->method('all')->willReturn([]);

        $site = $this->createMock(Site::class);
        $site->method('getWorkflows')->willReturn($workflows);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('notice');
        $logger->method('warning')->willReturnCallback(function (string $msg, array $ctx = []) use (&$logWarnings) {
            $logWarnings[] = preg_replace_callback('/\{(\w+)\}/', fn($m) => $ctx[$m[1]] ?? $m[0], $msg);
        });

        $command = $this->getMockBuilder(WatchCommand::class)
            ->onlyMethods(['getSiteById', 'getConfig', 'log', 'sleep'])
            ->getMock();

        $command->method('getSiteById')->willReturn($site);
        $command->method('getConfig')->willReturn(new Config(['date_format' => 'Y-m-d']));
        $command->method('log')->willReturn($logger);
        $command->method('sleep')->willReturnCallback(function (int $seconds) use (&$sleepCalls) {
            $sleepCalls[] = $seconds;
        });

        return $command;
    }

    /**
     * Polls within the warmup window should use the fast interval;
     * polls after warmup should use the slow interval.
     */
    public function testPollIntervalSwitchesAfterWarmup(): void
    {
        $sleepCalls  = [];
        $logWarnings = [];

        $command = $this->buildCommand($sleepCalls, $logWarnings);
        $command->watch('site-id', ['timeout' => 15]);

        $warmup = WatchCommand::WORKFLOWS_WATCH_WARMUP;
        $fast   = WatchCommand::WORKFLOWS_WATCH_INTERVAL_FAST;
        $slow   = WatchCommand::WORKFLOWS_WATCH_INTERVAL_SLOW;

        $fastPolls = array_slice($sleepCalls, 0, $warmup);
        foreach ($fastPolls as $i => $interval) {
            $this->assertSame($fast, $interval, "Poll $i should use fast interval ({$fast}s)");
        }

        $slowPolls = array_slice($sleepCalls, $warmup);
        foreach ($slowPolls as $i => $interval) {
            $this->assertSame($slow, $interval, "Poll $i should use slow interval ({$slow}s)");
        }
    }

    /**
     * When the timeout is reached the command should log a warning containing
     * the configured timeout duration and the --timeout=0 escape hatch.
     */
    public function testTimeoutLogsWarningAndExits(): void
    {
        $sleepCalls  = [];
        $logWarnings = [];

        $command = $this->buildCommand($sleepCalls, $logWarnings);
        $command->watch('site-id', ['timeout' => 1]);

        $this->assertNotEmpty($logWarnings, 'Expected a timeout warning to be logged');
        $this->assertStringContainsString('1', $logWarnings[0]);
        $this->assertStringContainsString('--timeout=0', $logWarnings[0]);
    }

    /**
     * When --timeout=0 the command should run without triggering the timeout
     * guard. We verify by driving the loop to 20 polls via a sentinel exception
     * and confirming no warning was logged.
     */
    public function testZeroTimeoutMeansNoLimit(): void
    {
        $sleepCalls  = [];
        $logWarnings = [];
        $pollCount   = 0;

        $workflows = $this->createMock(Workflows::class);
        $workflows->method('fetchWithOperations')->willReturnSelf();
        $workflows->method('setData')->willReturnSelf();
        $workflows->method('lastCreatedAt')->willReturn(0);
        $workflows->method('lastFinishedAt')->willReturn(0);
        $workflows->method('all')->willReturnCallback(function () use (&$pollCount) {
            if (++$pollCount >= 20) {
                throw new \RuntimeException('sentinel: enough polls');
            }
            return [];
        });

        $site = $this->createMock(Site::class);
        $site->method('getWorkflows')->willReturn($workflows);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->method('notice');
        $logger->method('warning')->willReturnCallback(function (string $msg) use (&$logWarnings) {
            $logWarnings[] = $msg;
        });

        $command = $this->getMockBuilder(WatchCommand::class)
            ->onlyMethods(['getSiteById', 'getConfig', 'log', 'sleep'])
            ->getMock();

        $command->method('getSiteById')->willReturn($site);
        $command->method('getConfig')->willReturn(new Config(['date_format' => 'Y-m-d']));
        $command->method('log')->willReturn($logger);
        $command->method('sleep')->willReturnCallback(function (int $s) use (&$sleepCalls) {
            $sleepCalls[] = $s;
        });

        try {
            $command->watch('site-id', ['timeout' => 0]);
        } catch (\RuntimeException $e) {
            $this->assertSame('sentinel: enough polls', $e->getMessage());
        }

        $this->assertEmpty($logWarnings, 'No timeout warning should be logged when --timeout=0');
        $this->assertSame(20, $pollCount);
    }
}
