<?php

namespace Pantheon\Terminus\UnitTests\Commands\Self;

use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\Commands\Self\ClearCacheCommand;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ClearCacheCommandTest
 * Testing class for Pantheon\Terminus\Commands\Self\ClearCacheCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Self
 */
class ClearCacheCommandTest extends CommandTestCase
{
    /**
     * Tests the self:clear-cache commadn
     */
    public function testClearCache()
    {
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $token = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token->expects($this->once())
            ->method('delete');

        $token2 = $this->getMockBuilder(SavedToken::class)
            ->disableOriginalConstructor()
            ->getMock();
        $token2->expects($this->once())
            ->method('delete');

        $tokens = $this->getMockBuilder(SavedTokens::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMembers'])
            ->getMock();
        $tokens->expects($this->any())
            ->method('getMembers')
            ->willReturn([$token, $token2]);

        $this->session->expects($this->any())
            ->method('getTokens')
            ->willReturn($tokens);
        $this->session->expects($this->once())
            ->method('destroy');


        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Your saved machine tokens have been deleted and you have been logged out.')
            );

        $command = new ClearCacheCommand();
        $command->setConfig($this->config);
        $command->setLogger($this->logger);
        $command->setSession($this->session);

        $command->clearCache();
    }
}
