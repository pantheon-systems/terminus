<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use Pantheon\Terminus\Commands\Self\ClearCacheCommand;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ClearCacheCommandTest
 * Testing class for Pantheon\Terminus\Commands\Self\ClearCacheCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Self
 */
class ClearCacheCommandTest extends CommandTestCase
{
    /**
     * Tests the self:clear-cache command
     */
    public function testClearCache()
    {
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('The local Terminus cache has been cleared.')
            );

        $command = new ClearCacheCommand();
        $command->setLogger($this->logger);

        $command->clearCache();
    }
}
