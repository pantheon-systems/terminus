<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\ArtCommand;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ArtCommandTest
 * Testing class for Pantheon\Terminus\Commands\ArtCommand
 * @package Pantheon\Terminus\UnitTests\Commands
 */
class ArtCommandTest extends CommandTestCase
{
    protected $command;

    protected function setUp()
    {
        parent::setUp();
        $this->command = new ArtCommand();
        $this->command->setConfig($this->config);
        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->getMock();

        $formatter = $this->getMockBuilder(OutputFormatterInterface::class)
            ->getMock();
        $this->output->method('getFormatter')->willReturn($formatter);
        $this->command->setOutput($this->output);
    }

    /**
     * @test
     */
    public function artCommandPrintsContentsOfFilesInAssetsDirectory()
    {
        $this->output->expects($this->once())
            ->method('writeln')
            ->with($this->stringContains('Hello World!'));
        $this->command->art('hello');
    }

    /**
     * @test
     */
    public function artCommandRejectsFilesNotInAssetsDirectory()
    {
        $this->setExpectedException(TerminusNotFoundException::class, 'There is no source for the requested foo artwork.');
        $this->command->art('foo');
    }
}
