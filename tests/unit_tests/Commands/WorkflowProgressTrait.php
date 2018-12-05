<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use League\Container\Container;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Trait WorkflowProgressTrait
 * @package Pantheon\Terminus\UnitTests\Commands
 */
trait WorkflowProgressTrait
{
    protected $container;
    protected $command;
    protected $progress_bar;

    public function expectWorkflowProcessing()
    {
        $this->container = $this->getMockBuilder(Container::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->progress_bar = $this->getMockBuilder(WorkflowProgressBar::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->command->setContainer($this->container);

        $this->container->method('get')
            ->with(WorkflowProgressBar::class)
            ->willReturn($this->progress_bar);
        $this->progress_bar->method('cycle')
            ->with()
            ->willReturn(null);
    }
}
