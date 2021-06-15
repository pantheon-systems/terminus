<?php


namespace Pantheon\Terminus\Helpers\Traits;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Psr\Log\LoggerAwareTrait;

/**
 * Trait CommandExecutorTrait
 *
 * @package D9ify\Traits
 */
trait CommandExecutorTrait
{
    use LoggerAwareTrait;

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
        $commandToExecute = vsprintf($formatString, $replacementValues);
        $this->logger->debug(
            "executing command: {command}" . PHP_EOL,
            ["command" => $commandToExecute]
        );
        exec(
            $commandToExecute,
            $result,
            $this->lastStatus
        );
        if ($this->lastStatus !== 0 && !$this->commandExists($commandToExecute)) {
            throw new TerminusNotFoundException(
                sprintf(
                    "The following command returned an error because of an uninstalled executable dependency: %s",
                    $commandToExecute
                )
            );
        }
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

    /**
     * Remove exec result.
     */
    public function clearExecResult(): void
    {
        $this->execResult = [];
    }

    /**
     * @param string $commandToExecute
     *
     * @return bool
     */
    protected function commandExists(string $commandToExecute): bool
    {
        [$executable] = explode(" ", $commandToExecute);
        exec(sprintf("which %s", $executable), $result);
        return strpos("not found", $result) === false;
    }
}
