<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\SwitchUpstreamCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class SwitchUpstreamCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\SwitchUpstreamCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class SwitchUpstreamCommandTest extends CommandTestCase
{
    protected $command;
    protected $workflow;

    /**
   * @inheritdoc
   */
    protected function setup()
    {
        parent::setUp();

        $this->command = new SwitchUpstreamCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


  /**
   * Exercises the site:switchupstream command
   */
    public function testSwitchUpstream()
    {
        $site_name = 'my-site';
        $upstream_id = 'upstreamid';

        $this->logger->expects($this->at(0))
        ->method('log')->with(
            $this->equalTo('warning'),
            $this->equalTo('This functionality is experimental. Do not use this on production sites.')
        );

        $this->site
            ->method('getName')
            ->willReturn($site_name);

        $this->expectConfirmation();
        $this->site->expects($this->once())
            ->method('switchUpstream')
            ->with($upstream_id)
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);

        $this->logger->expects($this->at(1))
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo('Switched upstream for {site}'),
              $this->equalTo(['site' => $site_name,])
          );

        $out = $this->command->switchUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }

  /**
   * Exercises the site:switchupstream command when declining the confirmation
   *
   * @todo Remove this when removing TerminusCommand::confirm()
   */
    public function testSwitchUpstreamConfirmationDecline()
    {
        $site_name = 'my-site';
        $upstream_id = 'upstreamid';

        $this->logger->expects($this->once())
          ->method('log')->with(
              $this->equalTo('warning'),
              $this->equalTo('This functionality is experimental. Do not use this on production sites.')
          );

        $this->expectConfirmation(false);
        $this->site->expects($this->never())
        ->method('switchUpstream');

        $out = $this->command->switchUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }

  /**
   * Exercises the site:delete command when Site::delete fails to ensure message gets through
   */
    public function testSwitchUpstreamFailure()
    {
        $site_name = 'my-site';
        $upstream_id = 'upstreamid';
        $exception_message = 'Error message';

        $this->logger->expects($this->once())
        ->method('log')->with(
            $this->equalTo('warning'),
            $this->equalTo('This functionality is experimental. Do not use this on production sites.')
        );

        $this->expectConfirmation();
        $this->site->expects($this->once())
        ->method('switchUpstream')
        ->with()
        ->will($this->throwException(new \Exception($exception_message)));

        $this->setExpectedException(\Exception::class, $exception_message);

        $out = $this->command->switchUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }
}
