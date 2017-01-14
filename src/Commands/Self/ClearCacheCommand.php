<?php

namespace Pantheon\Terminus\Commands\Self;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ClearCacheCommand
 * @package Pantheon\Terminus\Commands\Self
 */
class ClearCacheCommand extends TerminusCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * Clears the local Terminus session cache and all locally saved machine tokens.
     *
     * @command self:clear-cache
     * @aliases self:cc
     *
     * @usage terminus self:clear-cache
     *     Clears the local Terminus session cache and all locally saved machine tokens.
     */
    public function clearCache()
    {
        $tokens  = $this->session()->getTokens();

        $tokens->deleteAll();
        $this->session()->destroy();
        $this->log()->notice('Your saved machine tokens have been deleted and you have been logged out.');
    }
}
