<?php


namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\ClearCacheCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class ClearCacheCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\ClearCacheCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class ClearCacheCommandTest extends EnvCommandTest
{
    use WorkflowProgressTrait;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->command = new ClearCacheCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->expectWorkflowProcessing();
    }

    public function testGetClearCache()
    {
        $workflow = $this->getMockBuilder(Workflow::class)
          ->disableOriginalConstructor()
          ->getMock();
        $site_name = 'site_name';
        $this->environment->id = 'site_id';
        $this->environment->expects($this->once())
          ->method('clearCache')
          ->with()
          ->willReturn($workflow);
        $this->site->expects($this->any())
          ->method('get')
          ->willReturn(null);
        $this->logger->expects($this->once())
          ->method('log')
          ->with(
              $this->equalTo('notice'),
              $this->equalTo('Caches cleared on {site}.{env}.')
          );

        $out = $this->command->clearCache("$site_name.{$this->environment->id}");
        $this->assertNull($out);
    }
}
