<?php

namespace Pantheon\Terminus\Exceptions;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface D9ifyExceptionInterface
 * @package D9ify\Exceptions
 */
interface D9ifyExceptionInterface
{

    /**
     * D9ifyExceptionInterface constructor.
     * @param array $commandOutput
     * @param OutputInterface $output
     */
    public function __construct(array $commandOutput, OutputInterface $output);

    /**
     * @return string
     */
    public function __toString(): string;
}
