<?php

namespace Pantheon\Terminus\Exceptions;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class d9ExceptionBase
 * @package D9ify\Exceptions
 */
abstract class D9ifyExceptionBase extends \Exception implements D9ifyExceptionInterface
{

    protected static string $MESSAGE_TEXT = "";

    /**
     * @var OutputInterface
     */
    protected OutputInterface $output;

    /**
     * @var array
     */
    protected array $commandOutput;

    /**
     * d9ExceptionBase constructor.
     * @param array $commandOutput
     * @param OutputInterface $output
     */
    public function __construct(array $commandOutput, OutputInterface $output)
    {
        parent::__construct(static::$MESSAGE_TEXT, 0, null);
        $this->output = $output;
        $this->commandOutput = $commandOutput;
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->getMessage() . PHP_EOL . join(PHP_EOL, $this->commandOutput);
    }
}
