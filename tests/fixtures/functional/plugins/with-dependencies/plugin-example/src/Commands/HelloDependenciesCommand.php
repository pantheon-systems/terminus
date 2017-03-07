<?php
/**
 * This command can be invoked by running `terminus hello`
 */

namespace Pantheon\TerminusHello\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Example\Trivial\Unit\LengthUnits;

/**
 * Say hello to the user
 */
class HelloDependenciesCommand extends TerminusCommand
{
    /**
     * @hook pre-init dependencies:hello
     */
    function preInit()
    {
        $this->check('pre-init');
    }

    /**
     * @hook post-init dependencies:hello
     */
    function postInit()
    {
        $this->check('post-init');
    }

    /**
     * Print the classic message to the log.
     *
     * @command dependencies:hello
     */
    function sayHello()
    {
        $this->check('main command implementation');
        $this->log()->notice("Hello, " . LengthUnits::YD . "!");
    }

    protected function check($label)
    {
        $not = class_exists(LengthUnits::class) ? '' : 'NOT ';
        $this->log()->notice("LengthUnits class {$not}found in $label.");
    }
}
