<?php

namespace Pantheon\Terminus\ProgressBars;

use Pantheon\Terminus\Config\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class TerminusProgressBar
 *
 * An abstract class to reduce code repetition
 *
 * @package Pantheon\Terminus\ProgressBars
 */
abstract class TerminusProgressBar implements ConfigAwareInterface
{
    use ConfigAwareTrait;

    /**
     * @var ProgressBar
     */
    protected $progress_bar;

    /**
     * Stops the progress bar
     */
    protected function end()
    {
        $this->progress_bar->clear();
    }

    /**
     * Sleeps to prevent spamming the API.
     */
    protected function sleep()
    {
        $retry_interval = $this->getConfig()->get('http_retry_delay_ms', 100);
        usleep($retry_interval * 1000);
    }

    /**
     * Starts the progress bar and process
     */
    protected function start()
    {
        $this->progress_bar->start();
    }
}
