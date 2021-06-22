<?php

namespace Pantheon\Terminus\Bundlers;

use Composer\Script\Event;

/**
 * Interface BundlerInterface
 *
 * @package Pantheon\Terminus\Bundlers
 */
interface BundlerInterface
{

    /**
     * @param \Composer\Script\Event $event
     */
    public static function bundle(Event $event): ?string;
}
