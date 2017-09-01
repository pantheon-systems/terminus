<?php


namespace Pantheon\Terminus\UnitTests\Commands\Site\Upstream;

use Pantheon\Terminus\Commands\Site\Upstream\ClearCacheCommand;
use Pantheon\Terminus\Models\SiteUpstream;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ClearCacheCommandTest
 * Testing class for Pantheon\Terminus\Commands\Site\Upstream\ClearCacheCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Upstream
 */
class ClearCacheCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ClearCacheCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
    }

    public function testClearCache()
    {
        $upstream = $this->getMockBuilder(SiteUpstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $site_name = 'site_name';

        $this->site->expects($this->once())
            ->method('getUpstream')
            ->with()
            ->willReturn($upstream);
        $upstream->expects($this->once())
            ->method('clearCache')
            ->with()
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->site->method('get')->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Code cache cleared on {site}.'),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->clearCache($site_name);
        $this->assertNull($out);
    }
}
