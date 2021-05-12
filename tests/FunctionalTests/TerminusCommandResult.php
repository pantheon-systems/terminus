<?php

namespace Pantheon\Terminus\FunctionalTests;

/**
 * Class TerminusCommandResult
 *
 * @package Pantheon\Terminus\FunctionalTests
 */
class TerminusCommandResult
{

    /**
     * @var array
     */
    protected $output;
    /**
     * @var int
     */
    protected $status;

    /**
     * TerminusCommandResult constructor.
     *
     * @param array $output
     * @param int $status
     */
    public function __construct(array $output, int $status)
    {
        $this->setOutput($output);
        $this->setStatus($status);
    }

    /**
     * @return array
     */
    public function getOutput(): array
    {
        return $this->output;
    }

    /**
     * @param array $output
     */
    public function setOutput(array $output): void
    {
        $this->output = $output;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isError() : bool
    {
        return ($this->getStatus() !== 0);
    }

    /**
     * @return bool
     */
    public function isSuccess() : bool
    {
        return ($this->getStatus() === 0);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return join(PHP_EOL, $this->getOutput());
    }
}
