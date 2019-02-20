<?php
/**
 * This command can be invoked by running `terminus hello`
 */

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Say hello to the user
 */
class HelloCommand extends TerminusCommand
{
    /**
     * Print the classic message to the log.
     *
     * @command global:hello
     */
    function sayHello()
    {
        $this->log()->notice("Hello, World!");
    }
}
