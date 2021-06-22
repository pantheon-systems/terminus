<?php

namespace Pantheon\Terminus\Bundlers;

use Composer\Script\Event;
use Robo\Common\IO;

/**
 * Class MacosBundler
 *
 * @package Pantheon\Terminus\Bundlers
 */
class MacosBundler implements BundlerInterface
{
    use IO;

    /**
     * MacosBundler constructor.
     *
     * @param \Composer\Script\Event $event
     */
    public function __construct(Event $event)
    {
        $this->io = $event->getIO();
    }

    /**
     * @param \Composer\Script\Event $event
     *
     * @throws \Exception
     */
    public static function bundle(Event $event): ?string
    {
        $runner = new static($event);
        return $runner->run();
    }

    /**
     * @throws \Exception
     */
    public function run()
    {
        throw new \Exception("To Be Written");
    }
}
