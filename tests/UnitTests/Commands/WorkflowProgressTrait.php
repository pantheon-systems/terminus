<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;

/**
 * Trait WorkflowProgressTrait
 * @package Pantheon\Terminus\UnitTests\Commands
 */
trait WorkflowProgressTrait
{
    /**
     * @var TerminusCommand
     */
    protected $command;
    /**
     * @var WorkflowProgressBar
     */
    protected $progress_bar;

    /**
     * Sets the test up to expect the configuration to return http_retry_delay_ms info
     */
    public function expectConfigHTTPRetry()
    {
        $this->config->method('get')
            ->with('http_retry_delay_ms', 100)
            ->willReturn(100);
    }

    /**
     * Sets the test up to expect the progress bar being retrieved from the mock container object
     */
    public function expectContainerRetrieval()
    {
        $this->container->method('get')
            ->with(WorkflowProgressBar::class)
            ->willReturn($this->getProgressBar());
    }

    /**
     * Sets the test up to expect interactivity
     */
    public function expectInteractiveInput()
    {
        $this->input->method('isInteractive')
            ->with()
            ->willReturn(true);
    }

    /**
     * Sets the test up to expect non-interactivity
     */
    public function expectNonInteractiveInput()
    {
        $this->input->method('isInteractive')
            ->with()
            ->willReturn(false);
    }

    /**
     * Sets the test up to expect the progress bar cycling
     */
    public function expectProgressBarCycling()
    {
        $this->getProgressBar()->method('cycle')
            ->with()
            ->willReturn(null);
    }

    /**
     * Sets the test up to expect the workflow to fetch
     */
    public function expectWorkflowFetching()
    {
        $this->workflow->method('fetch')
            ->with()
            ->willReturn($this->workflow);
    }

    /**
     * Sets the test up to expect the workflow to say it is finished
     */
    public function expectWorkflowFinishing()
    {
        $this->workflow->method('isFinished')
            ->with()
            ->willReturn(true);
    }

    /**
     * Sets the test up to expect the usual set of processes involved with workflow cycling
     */
    public function expectWorkflowProcessing()
    {
        $this->expectInteractiveInput();
        $this->expectContainerRetrieval();
        $this->expectProgressBarCycling();
    }

    /**
     * Sets the test up to expect the usual set of processes involved with workflow cycling
     */
    public function expectNonInteractiveWorkflowProcessing()
    {
        $this->expectNonInteractiveInput();
        $this->expectConfigHTTPRetry();
        $this->expectWorkflowFetching();
        $this->expectWorkflowFinishing();
    }

    /**
     * Lazy instantiator of the progress bar mock object
     *
     * @return WorkflowProgressBar
     */
    public function getProgressBar()
    {
        if (empty($this->progress_bar)) {
            $this->progress_bar = $this->getMockBuilder(WorkflowProgressBar::class)
                ->disableOriginalConstructor()
                ->getMock();
        }
        return $this->progress_bar;
    }
}
