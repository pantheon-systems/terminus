<?php

namespace Pantheon\Terminus\UnitTests\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class DummyCommand
 * DummyCommand to exercise TerminusCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Remote
 */
class DummyCommand extends TerminusCommand
{
    /**
     * @return TerminusStyle
     */
    public function dummyIO()
    {
        return $this->io();
    }
}
