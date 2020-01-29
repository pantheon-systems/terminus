<?php

namespace Pantheon\Terminus\ProgressBars;

use Pantheon\Terminus\Exceptions\TerminusException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Class ProcessProgressBar
 *
 * A progress bar that tracks the progress of a Symfony Process.
 *
 * @package Pantheon\Terminus\ProgressBars
 */
class ProcessProgressBar extends TerminusProgressBar
{
    /**
     * @var Process
     */
    protected $process;

    /**
     * ProcessProgressBar constructor.
     * @param OutputInterface $output
     * @param Process $process
     */
    public function __construct(OutputInterface $output, Process $process)
    {
        $this->process = $process;
        ProgressBar::setFormatDefinition('custom', '[%bar%]');
        $this->progress_bar = new ProgressBar($output);
        $this->progress_bar->setFormat('custom');
    }

    /**
     * Runs the progress bar until completion.
     * @param \Closure $callback Used for interactivity
     * @throws TerminusException
     */
    public function cycle($callback = null)
    {
        $this->start();
        while ($this->process->isRunning()) {
            $this->progress_bar->advance();
            $this->sleep();
        }
        $this->process->wait($callback);
        $this->end();
    }
}
