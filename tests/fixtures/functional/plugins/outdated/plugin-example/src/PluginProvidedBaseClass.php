<?php
/**
 * This file shows an example of a base class for Plugin commands.
 */

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Example base class
 */
class PluginProvidedBaseClass extends TerminusCommand
{
    /**
     * Provide a utility function
     */
    function whoToGreet()
    {
        return 'everyone';
    }
}
