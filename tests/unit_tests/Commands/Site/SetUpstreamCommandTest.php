<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Commands\Site\Upstream\SetUpstreamCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class SetUpstreamCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\Upstream\SetUpstreamCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 */
class SetUpstreamCommandTest extends CommandTestCase
{
    protected $command;
    protected $workflow;

    /**
   * @inheritdoc
   */
    protected function setup()
    {
        parent::setUp();

        $this->command = new \Pantheon\Terminus\Commands\Site\Upstream\SetUpstreamCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }


  /**
   * Exercises the site:upstream:set command
   */
    public function testSetUpstream()
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
            ->method('setUpstream')
            ->with($upstream_id)
            ->willReturn($this->workflow);

        $this->workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);

        $this->logger->expects($this->at(1))
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo('Set upstream for {site}'),
              $this->equalTo(['site' => $site_name,])
          );

        $out = $this->command->setUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }

  /**
   * Exercises the site:upstream:set command when declining the confirmation
   *
   * @todo Remove this when removing TerminusCommand::confirm()
   */
    public function testSetUpstreamConfirmationDecline()
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
        ->method('setUpstream');

        $out = $this->command->setUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }

  /**
   * Exercises the site:upstream:set command when Site::delete fails to ensure message gets through
   */
    public function testSetUpstreamFailure()
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
        ->method('setUpstream')
        ->with()
        ->will($this->throwException(new \Exception($exception_message)));

        $this->setExpectedException(\Exception::class, $exception_message);

        $out = $this->command->setUpstream($site_name, $upstream_id);
        $this->assertNull($out);
    }
}
