<?php

namespace Pantheon\Terminus\UnitTests\Commands\Console;

use Pantheon\Terminus\Commands\Self\ConsoleCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\UnitTests\Commands\Env\EnvCommandTest;
use Psr\Log\LoggerInterface;

/**
 * Class ConsoleCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Self\ConsoleCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Console
 */
class ConsoleCommandTest extends EnvCommandTest
{
    /**
     * @var TerminusConfig
     */
    protected $config;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ConsoleCommand();
        $this->command->setSites($this->sites);

        // We do not expect to use the config object in 7.1+
        if (!$this->isRunningVersion71()) {
            $this->config = $this->getMockBuilder(TerminusConfig::class)
                ->disableOriginalConstructor()
                ->getMock();
            $this->command->setConfig($this->config);
        }
    }

    /**
     * Tests the self:console command
     *
     * @todo Remove the check and incomplete from this test when PSYSH is updated to work with PHP 7.1.
     */
    public function testConsole()
    {
        if ($this->isRunningVersion71()) {
            $this->markTestIncomplete('Incompatible message only printed on PHP >= 7.1.');
        }

        $this->config->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test_mode_pass'))
            ->willReturn(false);
        $out = $this->command->console('site.env');
        $this->assertNull($out);
    }

    /**
     * Testing the self:console command when in an incompatible PHP version
     *
     * @todo Remove this test when PSYSH is updated to work with PHP 7.1.
     */
    public function testConsoleIncompatibleMessage()
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        if (!$this->isRunningVersion71()) {
            $this->config->expects($this->once())
                ->method('get')
                ->with($this->equalTo('test_mode_pass'))
                ->willReturn(true);
        }
        $logger->expects($this->once())
            ->method('error')
            ->with($this->equalTo('This command is not compatible with PHP 7.1.'));

        $this->command->setLogger($logger);
        $out = $this->command->console('site.env');
        $this->assertNull($out);
    }

    /**
     * Determines whether the code is being run by PHP 7.1
     *
     * @return boolean
     */
    private function isRunningVersion71()
    {
        return (version_compare(PHP_VERSION, '7.1.0') >= 0);
    }
}
