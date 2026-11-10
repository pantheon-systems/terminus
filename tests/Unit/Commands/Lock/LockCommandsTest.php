<?php

namespace Pantheon\Terminus\Tests\Unit\Commands\Lock;

use Consolidation\Config\Config;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use GuzzleHttp\Psr7\Response;
use Pantheon\Terminus\Commands\Lock\DisableCommand;
use Pantheon\Terminus\Commands\Lock\EnableCommand;
use Pantheon\Terminus\Commands\Lock\InfoCommand;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\PublicApi\PublicApiAwareInterface;
use Pantheon\Terminus\Tests\Unit\PublicApi\ClientTestHelper;
use Pantheon\Terminus\Tests\Unit\PublicApi\SiteEnvResolverTest;
use Pantheon\Terminus\Tests\Unit\PublicApi\WorkflowWaiterTest;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class LockCommandsTest extends TestCase
{
    private const SITE_ID = SiteEnvResolverTest::SITE_ID;

    private AbstractLogger $logger;

    /**
     * @param TerminusCommand&PublicApiAwareInterface $command
     */
    private function prepare($command, ClientTestHelper $h)
    {
        $this->logger = new class extends AbstractLogger {
            public array $notices = [];

            public function log($level, $message, array $context = []): void
            {
                $replace = [];
                foreach ($context as $k => $v) {
                    $replace['{' . $k . '}'] = (string) $v;
                }
                $this->notices[] = strtr((string) $message, $replace);
            }
        };
        $command->setConfig(new Config(['workflow_polling_delay_ms' => 1000]));
        $command->setLogger($this->logger);
        $command->setPublicApiClient($h->client);
        return $command;
    }

    private static function lookupResponses(array $envOverrides = []): array
    {
        return [
            new Response(200, [], json_encode(self::SITE_ID)),
            ClientTestHelper::json(200, SiteEnvResolverTest::environments($envOverrides)),
        ];
    }

    public function testEnablePostsLockAndWaitsForWorkflow(): void
    {
        $h = new ClientTestHelper(array_merge(self::lookupResponses(), [
            // POST returns an already-finished payload, so the test does not sleep through a poll.
            ClientTestHelper::json(200, WorkflowWaiterTest::payload('FINISHED', 'SUCCEEDED')),
        ]));
        $command = $this->prepare(new EnableCommand(), $h);

        $command->enable('my-site.dev', 'test_user', 'test_password');

        $this->assertSame([
            'GET /v1/sites/by-name/my-site',
            'GET /v1/sites/' . self::SITE_ID . '/environments',
            'POST /v1/sites/' . self::SITE_ID . '/environments/dev/lock',
        ], $h->sent());
        $post = $h->history[2]['request'];
        $this->assertSame(
            [
                'siteId' => self::SITE_ID,
                'environment' => 'dev',
                'username' => 'test_user',
                'password' => 'test_password',
            ],
            json_decode((string) $post->getBody(), true)
        );
        $this->assertSame('', $post->getUri()->getQuery(), 'the password must not ride in the query string');
        $this->assertContains('my-site.dev has been locked.', $this->logger->notices);
    }

    public function testEnableFailsWhenWorkflowFails(): void
    {
        $h = new ClientTestHelper(array_merge(self::lookupResponses(), [
            ClientTestHelper::json(200, WorkflowWaiterTest::payload('FINISHED', 'FAILED')),
        ]));
        $command = $this->prepare(new EnableCommand(), $h);

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('failed (result: FAILED');
        $command->enable('my-site.dev', 'u', 'p');
    }

    public function testDisableDeletesLock(): void
    {
        $h = new ClientTestHelper(array_merge(self::lookupResponses(), [
            ClientTestHelper::json(200, WorkflowWaiterTest::payload('FINISHED', 'SUCCEEDED')),
        ]));
        $command = $this->prepare(new DisableCommand(), $h);

        $command->disable('my-site.dev');

        $this->assertSame('DELETE /v1/sites/' . self::SITE_ID . '/environments/dev/lock', $h->sent()[2]);
        $this->assertContains('my-site.dev has been unlocked.', $this->logger->notices);
    }

    public function testInfoUnlocked(): void
    {
        $h = new ClientTestHelper(self::lookupResponses());
        $command = $this->prepare(new InfoCommand(), $h);

        $list = $command->info('my-site.dev');

        $this->assertInstanceOf(PropertyList::class, $list);
        $this->assertSame(['locked' => false, 'username' => null, 'password' => null], $list->getArrayCopy());
    }

    public function testInfoLocked(): void
    {
        $h = new ClientTestHelper(self::lookupResponses([
            'test' => ['isLocked' => true, 'username' => 'test_user', 'password' => 'test_password'],
        ]));
        $command = $this->prepare(new InfoCommand(), $h);

        $list = $command->info('my-site.test');

        $this->assertSame(
            ['locked' => true, 'username' => 'test_user', 'password' => 'test_password'],
            $list->getArrayCopy()
        );
    }

    public function testInfoRequiresEnvironment(): void
    {
        $h = new ClientTestHelper([]);
        $command = $this->prepare(new InfoCommand(), $h);

        $this->expectExceptionMessage('<site_name>.<environment>');
        $command->info('my-site');
    }
}
