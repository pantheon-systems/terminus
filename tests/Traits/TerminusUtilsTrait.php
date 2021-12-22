<?php

namespace Pantheon\Terminus\Tests\Traits;

/**
 * Trait TerminusUtilsTrait.
 *
 * @package Pantheon\Terminus\Tests\Traits
 */
trait TerminusUtilsTrait
{
    /**
     * Asserts the command exists.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandExists(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringContainsString($commandName, $commandList);
    }

    /**
     * Asserts the command does not exist.
     *
     * @param string $commandName
     *   The command name to assert.
     */
    protected function assertCommandDoesNotExist(string $commandName)
    {
        $commandList = $this->terminus('list');
        $this->assertStringNotContainsString($commandName, $commandList);
    }
}
