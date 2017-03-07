<?php
/**
 * This command can be invoked by running `terminus with-base-class:hello`
 */

namespace Pantheon\TerminusHello\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Say hello to the user
 */
class HasBaseClassCommand extends PluginProvidedBaseClass
{
    /**
     * Print the classic message to the log.
     *
     * @command with-base-class:hello
     */
    function sayHello()
    {
        $who = $this->whoToGreet();
        $this->log()->notice("Hello, $who!");
    }
}
