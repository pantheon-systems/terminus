<?php

namespace Pantheon\Terminus\UnitTests\Commands\Connection;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\Connection\InfoCommand;
use Pantheon\Terminus\Config;

use Prophecy\Prophet;
use Terminus\Models\Environment;
use Terminus\Models\Site;
use VCR\VCR;

/**
 * Test suite for class for Pantheon\Terminus\Commands\Connection\InfoCommand
 */
class InfoCommandTest extends ConnectionCommandTest
{
    private $prophet;

    /**
     * Test suite setup
     *
     * @return void
     */
    protected function setup()
    {
        parent::setUp();

        $this->command = new InfoCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->prophet = new Prophet;
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->prophet->checkPredictions();
    }


    /**
     * Exercises connection:info command with a valid environment
     *
     * @return void
     *
     * @vcr site_connection-info
     */
    public function testConnectionInfo()
    {
        // command output with a valid site
        $out = $this->command->connectionInfo('behat-tests.dev');

        // should return a RowOfFields object
        $this->assertInstanceOf(RowsOfFields::class, $out);

        // should have a field structure
        $connection_info = $out->getArrayCopy();
        $this->assertEquals(['env', 'param', 'value'], array_keys($connection_info[0]));

        // should contain connection parameters
        $parameters = array_column($connection_info, 'param');
        $this->assertContains('sftp_command', $parameters);
        $this->assertContains('git_command', $parameters);
        $this->assertContains('mysql_command', $parameters);
        $this->assertContains('redis_command', $parameters);
    }

    /**
     * Exercises connection:info command with a valid environment and filter
     *
     * @return void
     *
     * @vcr site_connection-info
     */
    public function testConnectionInfoFilter()
    {
        // command output using a filter argument
        $out        = $this->command->connectionInfo('behat-tests.dev', 'git_command');
        $parameters = array_column($out->getArrayCopy(), 'param');

        // assert parameter count
        $this->assertCount(1, $parameters);

        // assert filtered field
        $this->assertContains('git_command', $parameters);
    }

    /**
     * Exercises connection:info command without a valid environment argument
     *
     * @return void
     */
    public function testConnectionInfoInvalid()
    {
        // should display an error message
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('error'),
                $this->equalTo('The environment argument must be given as <site_name>.<environment>')
            );

        // should return the correct type and structure
        $out = $this->command->connectionInfo('invalid-env');
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([[]], $out->getArrayCopy());
    }

    /**
     * Exercises the environmentParams protected method
     *
     * @return void
     */
    public function testEnvironmentParams()
    {
        $site_prophet = $this->prophet->prophesize(Site::class);
        $site_prophet->get('name')->willReturn('my_site');
        $site = $site_prophet->reveal();

        $env_prophet = $this->prophet->prophesize(Environment::class);
        $env_prophet->connectionInfo()->willReturn([
            'param_a' => 'value_a',
            'param_b' => 'value_b',
        ]);
        $environment       = $env_prophet->reveal();
        $environment->id   = 'my_env';
        $environment->site = $site;

        // should return all parameters
        $this->assertEquals(
            [
                ['env' => 'my_site.my_env', 'param' => 'param_a', 'value' => 'value_a'],
                ['env' => 'my_site.my_env', 'param' => 'param_b', 'value' => 'value_b'],
            ],
            $this->protectedMethodCall($this->command, 'environmentParams', [$environment])
        );
    }

    /**
     * Exercises the environmentParams protected method with a filter argument
     *
     * @return void
     */
    public function testEnvironmentParamsFilter()
    {
        $site_prophet = $this->prophet->prophesize(Site::class);
        $site_prophet->get('name')->willReturn('my_site');
        $site = $site_prophet->reveal();

        $env_prophet = $this->prophet->prophesize(Environment::class);
        $env_prophet->connectionInfo()->willReturn([
            'param_a' => 'value_a',
            'param_b' => 'value_b',
        ]);
        $environment       = $env_prophet->reveal();
        $environment->id   = 'my_env';
        $environment->site = $site;

        // should return only filtered parameter
        $this->assertEquals(
            [
                ['env' => 'my_site.my_env', 'param' => 'param_b', 'value' => 'value_b'],
            ],
            $this->protectedMethodCall($this->command, 'environmentParams', [$environment, 'param_b'])
        );
    }
}
