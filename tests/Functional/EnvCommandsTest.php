<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class EnvCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class EnvCommandsTest extends TerminusTestBase
{
    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\ClearCacheCommand
     *
     * @group env
     * @group short
     */
    public function testClearCacheCommand()
    {
        $this->terminus(sprintf('env:clear-cache %s', $this->getSiteEnv()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\DeployCommand
     *
     * @group env
     * @group short
     */
    public function testDeployCommand()
    {
        $this->terminus(sprintf('env:deploy %s.%s', $this->getSiteName(), 'live'));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\CloneContentCommand
     *
     * @group env
     * @group long
     */
    public function testCloneContentCommand()
    {
        $this->terminus(sprintf('env:clone-content %s.%s %s', $this->getSiteName(), 'dev', $this->getMdEnv()));
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\CodeLogCommand
     *
     * @group env
     * @group short
     */
    public function testCodelogCommand()
    {
        $codeLogs = $this->terminusJsonResponse(sprintf('env:code-log %s', $this->getSiteEnv()));
        $this->assertIsArray($codeLogs);
        $this->assertNotEmpty($codeLogs);
        $codeLog = array_shift($codeLogs);
        $this->assertIsArray($codeLog, 'A code log should be an array.');
        $this->assertNotEmpty($codeLog);
        $this->assertArrayHasKey(
            'datetime',
            $codeLog,
            'A code log should have "datetime" field.'
        );
        $this->assertArrayHasKey(
            'author',
            $codeLog,
            'A code log should have "author" field.'
        );
        $this->assertArrayHasKey(
            'labels',
            $codeLog,
            'A code log should have "labels" field.'
        );
        $this->assertArrayHasKey(
            'message',
            $codeLog,
            'returned codelog should have "message" field.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\DiffStatCommand
     * @covers \Pantheon\Terminus\Commands\Env\CommitCommand
     *
     * @group env
     * @group long
     */
    public function testCommitAndDiffStatCommands()
    {
        if (!extension_loaded('ssh2')) {
            $this->markTestSkipped(
                'PECL SSH2 extension for PHP is required to run this test.'
            );
        }

        $siteEnv = $this->getSiteEnv();

        // Enable SFTP mode.
        $this->terminus(sprintf('connection:set %s sftp', $siteEnv));
        sleep(60);

        // Check the diff - no diff is expected.
        $diff = $this->terminusJsonResponse(sprintf('env:diffstat %s', $siteEnv));
        $this->assertEquals([], $diff);

        // Upload a test file to the site.
        $fileName = $this->uploadTestFileToSite($siteEnv, 'code');

        // Check the diff.
        $expectedDiff = [
            [
                'file' => $fileName,
                'status' => 'A',
                'deletions' => '0',
                'additions' => '1',
            ],
        ];
        $this->assertTerminusCommandResultEqualsInAttempts(function () use ($siteEnv) {
            return $this->terminusJsonResponse(sprintf('env:diffstat %s', $siteEnv));
        }, $expectedDiff);

        // Commit the changes.
        $this->terminus(
            sprintf(
                'env:commit %s --message="%s"',
                $siteEnv,
                sprintf('Add test file %s', $fileName)
            )
        );
        sleep(60);

        // Check the diff - no diff is expected.
        $this->assertTerminusCommandResultEqualsInAttempts(function () use ($siteEnv) {
            return $this->terminusJsonResponse(sprintf('env:diffstat %s', $siteEnv));
        }, []);
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\InfoCommand
     *
     * @group env
     * @group short
     */
    public function testInfoCommand()
    {
        $envInfo = $this->terminusJsonResponse(sprintf('env:info %s', $this->getSiteEnv()));
        $this->assertIsArray($envInfo);
        $this->assertArrayHasKey(
            'id',
            $envInfo,
            'Environment info should have "id" field.'
        );
        $this->assertArrayHasKey(
            'created',
            $envInfo,
            'Environment info should have "created" field.'
        );
        $this->assertArrayHasKey(
            'domain',
            $envInfo,
            'Environment info should have "domain" field.'
        );
        $this->assertArrayHasKey(
            'php_version',
            $envInfo,
            'Environment info should have "php_version" field.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\MetricsCommand
     *
     * @group env
     * @group short
     */
    public function testMetricsCommand()
    {
        $metrics = $this->terminusJsonResponse(sprintf('env:metrics %s', $this->getSiteEnv()));
        $this->assertIsArray($metrics);
        $this->assertNotEmpty($metrics);
        $this->assertArrayHasKey('timeseries', $metrics, 'Metrics should have "timeseries" field.');
        $metric = array_shift($metrics['timeseries']);
        $this->assertIsArray($metric);
        $this->assertNotEmpty($metric);
        $this->assertArrayHasKey(
            'datetime',
            $metric,
            'A metric should have "datetime" field.'
        );
        $this->assertArrayHasKey(
            'visits',
            $metric,
            'A metric should have "visits" field.'
        );
        $this->assertArrayHasKey(
            'pages_served',
            $metric,
            'A metric should have "pages_served" field.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\CacheMetricsCommand
     *
     * @group env
     * @group short
     */
    public function testCacheMetricsCommand()
    {
        $cacheMetrics = $this->terminusJsonResponse(sprintf('env:cache-metrics %s', $this->getSiteEnv()));
        $this->assertIsArray($cacheMetrics);
        $this->assertNotEmpty($cacheMetrics);
        $this->assertArrayHasKey('timeseries', $cacheMetrics, 'Cache Metrics should have "timeseries" field.');
        $cacheMetric = array_shift($cacheMetrics['timeseries']);
        $this->assertIsArray($cacheMetric);
        $this->assertNotEmpty($cacheMetric);
        $this->assertArrayHasKey(
            'time',
            $cacheMetric,
            'A cache metric should have "time" field.'
        );
        $this->assertArrayHasKey(
            'pages_served',
            $cacheMetric,
            'A cache metric should have "pages_served" field.'
        );
        $this->assertArrayHasKey(
            'cache_hits',
            $cacheMetric,
            'A cache metric should have "cache_hits" field.'
        );
        $this->assertArrayHasKey(
            'cache_misses',
            $cacheMetric,
            'A cache metric should have "cache_misses" field.'
        );
        $this->assertArrayHasKey(
            'cache_hit_ratio',
            $cacheMetric,
            'A cache metric should have "cache_hit_ratio" field.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\ListCommand
     *
     * @group env
     * @group short
     */
    public function testListCommand()
    {
        $envs = $this->terminusJsonResponse(sprintf('env:list %s', $this->getSiteName()));
        $this->assertIsArray($envs);
        $env = array_shift($envs);

        $this->assertArrayHasKey(
            'id',
            $env,
            'An environment should have "id" field.'
        );
        $this->assertArrayHasKey(
            'created',
            $env,
            'An environment should have "created" field.'
        );
        $this->assertArrayHasKey(
            'initialized',
            $env,
            'An environment should have "initialized" field.'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\ViewCommand
     *
     * @group env
     * @group short
     */
    public function testViewCommand()
    {
        $url = $this->terminus(sprintf('env:view %s --print', $this->getSiteEnv()));
        $expectedUrl = sprintf('https://%s-%s.pantheonsite.io/', $this->getMdEnv(), $this->getSiteName());
        $this->assertEquals($expectedUrl, $url);
    }
}
