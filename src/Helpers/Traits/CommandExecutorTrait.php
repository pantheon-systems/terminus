<?php


namespace Pantheon\Terminus\Helpers\Traits;

/**
 * Trait CommandExecutorTrait
 * @package D9ify\Traits
 */
trait CommandExecutorTrait
{

    /**
     * @var array
     */
    protected array $execResult = [];
    /**
     * @var int
     */
    protected int $lastStatus = 0;

    /**
     * @param string $formatString
     * @param array $replacementValues
     * @return array
     */
    public function execute(string $formatString, array $replacementValues = []): array
    {
        exec(
            vprintf($formatString, $replacementValues),
            $result,
            $this->lastStatus
        );
        $this->execResult += $result;
        return $result;
    }

    /**
     * @return array
     */
    public function getExecResult(): array
    {
        return $this->execResult;
    }

    /**
     * @return int
     */
    public function getLastStatus(): int
    {
        return $this->lastStatus;
    }

    public function clearExecResult(): void
    {
        $this->execResult = [];
    }
}
