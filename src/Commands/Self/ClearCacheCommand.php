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
     * Clear the local Terminus session and all saved machine tokens
     *
     * @command self:clear-cache
     * @aliases self:cc
     *
     * @usage terminus self:clear-cache
     *    Removes all Terminus cached data
     */
    public function clearCache()
    {
        $tokens  = $this->session()->getTokens();

        $tokens->deleteAll();
        $this->session()->destroy();
        $this->log()->notice('Your saved machine tokens have been deleted and you have been logged out.');
    }
}
