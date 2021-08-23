<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class EnvCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class EnvCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;
    use UrlStatusCodeHelperTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\ClearCacheCommand
     *
     * @group env
     * @group short
     */
    public function testClearCacheCommand()
    {
        $sitename = $this->getSiteName();
        $this->terminus("env:clear-cache $sitename.dev");
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
        $sitename = $this->getSiteName();
        $this->terminus("env:deploy $sitename.live");
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
        $sitename = $this->getSiteName();
        $this->terminus("env:clone $sitename.live test");
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
        $sitename = $this->getSiteName();
        $codeLogs = $this->terminusJsonResponse("env:code-log $sitename");
        $this->assertIsArray($codeLogs, "Returned data from codelogs should be json.");
        $codeLog = array_shift($codeLogs);
        $this->assertIsArray($codeLog, "Assert returned data from codelogs are made of codelog entries.");
        $this->assertArrayHasKey(
            'datetime',
            $codeLog,
            "returned codelog should have datetime property"
        );
        $this->assertArrayHasKey(
            'author',
            $codeLog,
            'returned codelog should have author property'
        );
        $this->assertArrayHasKey(
            'labels',
            $codeLog,
            "returned codelog should have datetime property"
        );
        $this->assertArrayHasKey(
            'message',
            $codeLog,
            'returned codelog should have author property'
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\DiffStatCommand
     * @covers \Pantheon\Terminus\Commands\Env\CommitCommand
     *
     * @group env
     * @group todo
     */
    public function testCommitCommand()
    {
        $this->fail("To Be Written");
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
        $sitename = $this->getSiteName();
        $info = $this->terminusJsonResponse("env:info $sitename.dev");
        $this->assertIsArray($info, "Assert returned data from environment is array.");
        $this->assertArrayHasKey(
            'id',
            $info,
            "returned codelog should have datetime property"
        );
        $this->assertArrayHasKey(
            'created',
            $info,
            'returned codelog should have author property'
        );
        $this->assertArrayHasKey(
            'domain',
            $info,
            "returned codelog should have datetime property"
        );
        $this->assertArrayHasKey(
            'php_version',
            $info,
            'returned codelog should have author property'
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
        // Randomly chosen customer site
        $sitename = $this->getSiteName();
        $metrics = $this->terminusJsonResponse("env:metrics $sitename.live");
        $this->assertIsArray($metrics, "Assert returned data from metrics are made of metrics entries.");
        $this->assertArrayHasKey('timeseries', $metrics, "Returned metrics should have a timeseries property.");
        $metric = array_shift($metrics['timeseries']);
        $this->assertIsArray($metric, "metrics returned data from metrics are made of metrics entries.");
        $this->assertArrayHasKey(
            'datetime',
            $metric,
            'returned codelog should have author property'
        );
        $this->assertArrayHasKey(
            'visits',
            $metric,
            'returned codelog should have author property'
        );
        $this->assertArrayHasKey(
            'pages_served',
            $metric,
            "returned codelog should have datetime property"
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
        $sitename = $this->getSiteName();
        $envs = $this->terminusJsonResponse("env:list $sitename");
        $this->assertIsArray($envs, "Assert returned data from list are made of env entries.");
        $env = array_shift($envs);

        $this->assertArrayHasKey(
            'id',
            $env,
            "returned env list should have datetime property"
        );
        $this->assertArrayHasKey(
            'created',
            $env,
            'returned env list should have author property'
        );
        $this->assertArrayHasKey(
            'initialized',
            $env,
            "returned env list should have datetime property"
        );
    }

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Env\ViewCommand
     *
     * @group env
     * @group long
     */
    public function testViewCommand()
    {
        $sitename = $this->getSiteName();
        $this->terminus("env:view $sitename.dev");
    }
}
