<?php

namespace Pantheon\Terminus\Commands;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * MultiCommand has a ref to the container, so that the main command
 * may call subcommands using the helper function.
 */
abstract class MultiCommand extends TerminusCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Call a subcommand.
     */
    protected function subCommand($cmd, $input, $output)
    {
        return $this->getContainer()->get('application')->find($cmd)->run($input, $output);
    }
}
