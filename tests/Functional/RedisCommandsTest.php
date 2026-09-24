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

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand
     *
     * @group redis
     * @group long
     */
    public function testRedisInfo()
    {
        $output = $this->terminus(sprintf('redis:info %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoMemory
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoMemory()
    {
        $output = $this->terminus(sprintf('redis:info:memory %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:memory command should return output');
        $this->assertStringContainsString('used_memory', $output);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoCpu
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoCpu()
    {
        $output = $this->terminus(sprintf('redis:info:cpu %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:cpu command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoStats
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoStats()
    {
        $output = $this->terminus(sprintf('redis:info:stats %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:stats command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoReplication
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoReplication()
    {
        $output = $this->terminus(sprintf('redis:info:replication %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:replication command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoClients
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoClients()
    {
        $output = $this->terminus(sprintf('redis:info:clients %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:clients command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoServer
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoServer()
    {
        $output = $this->terminus(sprintf('redis:info:server %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:server command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoPersistence
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoPersistence()
    {
        $output = $this->terminus(sprintf('redis:info:persistence %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:persistence command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoKeyspace
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoKeyspace()
    {
        $output = $this->terminus(sprintf('redis:info:keyspace %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:keyspace command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InfoCommand::infoCommandstats
     *
     * @group redis
     * @group long
     */
    public function testRedisInfoCommandstats()
    {
        $output = $this->terminus(sprintf('redis:info:commandstats %s.dev', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis info:commandstats command should return output');
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\FlushdbCommand
     *
     * @group redis
     * @group long
     */
    public function testRedisFlushdb()
    {
        $this->assertTerminusCommandSucceedsInAttempts(sprintf('redis:flushdb %s.dev', $this->getSiteName()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Redis\InsightCommand
     *
     * @group redis
     * @group long
     */
    public function testRedisInsightUrlOnly()
    {
        $output = $this->terminus(sprintf('redis:insight %s.dev --url-only', $this->getSiteName()));
        $this->assertNotEmpty($output, 'Redis insight --url-only should return URL');
        $this->assertStringContainsString('redis://', $output);
    }
}
