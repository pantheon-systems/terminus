<?php

namespace Pantheon\Terminus\Commands;

use Symfony\Component\Console\Output\OutputInterface;

class ArtCommand extends TerminusCommand
{

    /**
     * Displays Pantheon ASCII artwork
     *
     * @name art
     *
     * @param string $name Name of the artwork to select
     * @usage terminus art rocket
     *   Displays the rocket artwork
     */
    public function art($name) {
        // Symfony Style
        $this->io()->title("Symfony Style Example.");

        // Symfony OutputInterface
        $this->output()->writeln("Wow!");
        $this->output()->writeln("The verbosity level is " . $this->output->getVerbosity() . "\n");
        $this->output()->writeln("Print this message only in verbose mode", OutputInterface::VERBOSITY_VERBOSE);
        $this->output()->writeln("Print this message only in very verbose mode", OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->output()->writeln("Print this message only in debug mode", OutputInterface::VERBOSITY_DEBUG);

        // Robo logger
        $this->log()->warning('This is a warning log message. (Warnings always printed)');
        $this->log()->notice('This is a notice log message. (Also always printed)');
        $this->log()->info('This is an informational log message. (Verbose only)');
        $this->log()->debug('This is a debug log message. (Debug only)');
    }
}
