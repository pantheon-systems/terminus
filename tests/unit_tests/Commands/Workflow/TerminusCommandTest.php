<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Style\TerminusStyle;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TerminusCommandTest
 * Testing class for Pantheon\Terminus\Commands\TerminusCommand
 * @package Pantheon\Terminus\UnitTests\Commands
 */
class TerminusCommandTest extends CommandTestCase
{
    /**
     * @inherit
     */
    public function setUp()
    {
        parent::setUp();

        $this->output = $this->getMockBuilder(OutputInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command = new DummyCommand($this->getConfig());
        $this->command->setOutput($this->output);
        $this->command->setInput($this->input);
    }

    /**
     * Tests the io function
     */
    public function testIO()
    {
        $this->output->expects($this->once())
            ->method('getFormatter')
            ->willReturn(new OutputFormatter());
        $style = $this->command->dummyIO();
        $this->assertInstanceOf(TerminusStyle::class, $style);
    }
}
