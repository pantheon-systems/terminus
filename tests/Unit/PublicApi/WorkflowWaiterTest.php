<?php

namespace Pantheon\Terminus\Tests\Unit\PublicApi;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\PublicApi\WorkflowWaiter;
use PHPUnit\Framework\TestCase;

class WorkflowWaiterTest extends TestCase
{
    public const WID = '376f9726-d6db-11f1-8764-8a01339cc28a';

    public static function payload(string $phase = 'STARTED', ?string $result = null): array
    {
        return ['id' => self::WID, 'status' => self::status($phase, $result)];
    }

    public static function status(string $phase, ?string $result, array $extra = []): array
    {
        return $extra + [
            'id' => self::WID,
            'description' => 'Lock environment',
            'activeDescription' => 'Locking environment',
            'phase' => $phase,
            'result' => $result,
            'progress' => $phase === 'FINISHED' ? 100 : 50,
            'reason' => [],
        ];
    }

    public function testPollsUntilFinishedAndSucceeds(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, self::status('STARTED', 'ACTIVE')),
            ClientTestHelper::json(200, self::status('FINISHED', 'SUCCEEDED')),
        ]);
        $sleeps = [];
        $progress = [];
        $waiter = new WorkflowWaiter(
            $h->client,
            200,
            function (array $status) use (&$progress) {
                $progress[] = $status['progress'];
            },
            function (int $ms) use (&$sleeps) {
                $sleeps[] = $ms;
            }
        );

        $status = $waiter->wait('site-1', self::payload());

        $this->assertSame('SUCCEEDED', $status['result']);
        $this->assertSame([1000, 1000], $sleeps, 'poll delay is raised to the 1000ms minimum');
        $this->assertSame([50, 100], $progress);
        $this->assertSame([
            'GET /v1/sites/site-1/workflows/' . self::WID,
            'GET /v1/sites/site-1/workflows/' . self::WID,
        ], $h->sent());
    }

    public function testAlreadyFinishedPayloadDoesNotPoll(): void
    {
        $h = new ClientTestHelper([]);
        $waiter = new WorkflowWaiter($h->client, 1000, null, fn () => null);

        $status = $waiter->wait('site-1', self::payload('FINISHED', 'SUCCEEDED'));

        $this->assertSame('SUCCEEDED', $status['result']);
        $this->assertCount(0, $h->history);
    }

    public function testFailedWorkflowThrowsWithReasons(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, self::status('FINISHED', 'FAILED', ['reason' => ['Bad {thing}', 'Second']])),
        ]);
        $waiter = new WorkflowWaiter($h->client, 1000, null, fn () => null);

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage(
            'Lock environment failed (result: FAILED, workflow ' . self::WID . '): Bad {thing}; Second'
        );
        $waiter->wait('site-1', self::payload());
    }

    public function testAbortedWithoutReasons(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(200, self::status('FINISHED', 'ABORTED'))]);
        $waiter = new WorkflowWaiter($h->client, 1000, null, fn () => null);

        $this->expectExceptionMessage('Lock environment failed (result: ABORTED, workflow ' . self::WID . ').');
        $waiter->wait('site-1', self::payload());
    }

    public function testTimesOut(): void
    {
        $h = new ClientTestHelper(array_fill(0, 5, ClientTestHelper::json(200, self::status('STARTED', 'ACTIVE'))));
        $now = 100;
        $waiter = new WorkflowWaiter(
            $h->client,
            1000,
            null,
            function () use (&$now) {
                $now += 1;
            },
            function () use (&$now) {
                return $now;
            }
        );

        try {
            $waiter->wait('site-1', self::payload(), 3);
            $this->fail('Expected a timeout');
        } catch (TerminusException $e) {
            $this->assertSame('Workflow ' . self::WID . ' timed out after 3 seconds.', $e->getMessage());
        }
        $this->assertCount(3, $h->history);
    }

    public function testPayloadWithoutIdThrows(): void
    {
        $h = new ClientTestHelper([]);
        $this->expectExceptionMessage('did not include a workflow ID');
        (new WorkflowWaiter($h->client))->wait('site-1', ['status' => []]);
    }
}
