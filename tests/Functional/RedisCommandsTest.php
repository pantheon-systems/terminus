<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class RedisCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class RedisCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\EnableCommand
     *
     * @group redis
     * @group long
     */
    public function testRedisEnable()
    {
        // This usually runs right after site plan change, let's sleep for a bit before continuing.
        sleep(5);
        $this->assertTerminusCommandSucceedsInAttempts(sprintf('redis:enable %s', $this->getSiteName()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\DisableCommand
     *
     * @group redis
     * @group long
     */
    public function testRedisDisable()
    {
        $this->assertTerminusCommandSucceedsInAttempts(sprintf('redis:disable %s', $this->getSiteName()));
    }
}
