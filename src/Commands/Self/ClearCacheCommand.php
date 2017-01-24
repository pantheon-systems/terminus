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
     * Clears the local Terminus command cache.
     *
     * @command self:clear-cache
     * @aliases self:cc
     *
     * @usage terminus self:clear-cache
     *     Clears the local Terminus session cache and all locally saved machine tokens.
     */
    public function clearCache()
    {
        $this->log()->notice('The local Terminus cache has been cleared.');
    }
}
