<?php
/**
 * This command can be invoked by running `terminus with-base-class:hello`
 */

use Pantheon\Terminus\Commands\TerminusCommand;

// NOT RECOMMENDED: if you need to include more source files, define
// an 'autoload' section in your composer.json file instead, and use
// autoloading with a namespace.
include __DIR__ . '/PluginProvidedBaseClass.php';

/**
 * Say hello to the user
 */
class HasBaseClassCommand extends PluginProvidedBaseClass
{
    /**
     * Print the classic message to the log.
     *
     * @command with-global-base-class:hello
     */
    function sayHello()
    {
        $who = $this->whoToGreet();
        $this->log()->notice("Hello, $who!");
    }
}
