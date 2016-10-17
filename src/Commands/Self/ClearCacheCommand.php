<?php

namespace Pantheon\Terminus\Commands\Self;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;

class ClearCacheCommand extends TerminusCommand implements ContainerAwareInterface
{
    use ContainerAwareTrait;
    /**
     * Clears the local Terminus cache
     *
     * @command self:clear-cache
     *
     * @usage terminus self:clear-cache
     *   Clear the local Terminus cache, session and all saved tokens
     */
    public function clearCache()
    {
        $tokens  = $this->session()->getTokens();

        $tokens->deleteAll();
        $this->session()->destroy();
        $this->log()->notice('Your saved machine tokens have been deleted and you have been logged out.');
    }
}
