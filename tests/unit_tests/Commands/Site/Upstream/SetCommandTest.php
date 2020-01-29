<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Upstream;

use Pantheon\Terminus\Collections\SiteAuthorizations;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Commands\Site\Upstream\SetCommand;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SiteAuthorization;
use Pantheon\Terminus\Models\SiteUpstream;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class SetCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\Upstream\SetCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Upstream
 */
class SetCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var SiteAuthorization
     */
    protected $authorizations;
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var SiteUpstream
     */
    protected $site_upstream;
    /**
     * @var Upstream
     */
    protected $upstream;
    /**
     * @var Upstreams
     */
    protected $upstreams;
    /**
     * @var string[]
     */
    protected $upstream_data;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @inheritdoc
     */
    protected function setup()
    {
        $this->authorizations = $this->getMockBuilder(SiteAuthorizations::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstreams = $this->getMockBuilder(Upstreams::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->upstream = $this->getMockBuilder(Upstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_upstream = $this->getMockBuilder(SiteUpstream::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_upstream->id = 'Site upstream ID';
        $this->upstream_data = ['framework' => 'Framework', 'id' => 'upstream_id', 'label' => 'Upstream Name',];

        parent::setUp();

        $this->site->expects($this->once())
            ->method('getAuthorizations')
            ->with()
            ->willReturn($this->authorizations);

        $this->command = new SetCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setInput($this->input);
        $this->command->setSession($this->session);
        $this->command->setOutput($this->output);
        $this->expectWorkflowProcessing();

        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Exercises the site:upstream:set command
     */
    public function testSet()
    {
        $site_name = 'my-site';
        $upstream_id = $this->upstream_data['id'];

        $this->expectGetUpstream($upstream_id);

        $this->authorizations->expects($this->once())
            ->method('can')
            ->with('switch_upstream')
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getUpstream')
            ->with()
            ->willReturn($this->site_upstream);
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                'info',
                'To undo this change run `terminus site:upstream:set {site} {upstream}`',
                ['site' => $this->site->id, 'upstream' => $this->site_upstream->id,]
            );

        $this->site
            ->method('getName')
            ->willReturn($site_name);

        $this->expectConfirmation();
        $this->site->expects($this->once())
            ->method('setUpstream')
            ->with($upstream_id)
            ->willReturn($this->workflow);

        $this->logger->expects($this->at(1))
          ->method('log')->with(
              $this->equalTo('notice'),
              $this->equalTo('Set upstream for {site} to {upstream}'),
              $this->equalTo(['site' => $site_name, 'upstream' => $this->upstream_data['label']])
          );

        $out = $this->command->set($site_name, $upstream_id);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:upstream:set command when Site::delete() fails to ensure message gets through
     */
    public function testSetFailure()
    {
        $site_name = 'my-site';
        $upstream_id = $this->upstream_data['id'];
        $exception_message = 'Error message';

        $this->expectGetUpstream($upstream_id);

        $this->authorizations->expects($this->once())
            ->method('can')
            ->with('switch_upstream')
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getUpstream')
            ->with()
            ->willReturn($this->site_upstream);
        $this->logger->expects($this->at(0))
            ->method('log')->with(
                'info',
                'To undo this change run `terminus site:upstream:set {site} {upstream}`',
                ['site' => $this->site->id, 'upstream' => $this->site_upstream->id,]
            );
        $this->expectConfirmation();
        $this->site->expects($this->once())
        ->method('setUpstream')
        ->with()
        ->will($this->throwException(new \Exception($exception_message)));

        $this->setExpectedException(\Exception::class, $exception_message);

        $out = $this->command->set($site_name, $upstream_id);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:upstream:set command when the user does not have permission to do this
     */
    public function testSetInsufficientPermission()
    {
        $site_name = 'my-site';
        $upstream_id = $this->upstream_data['id'];

        $this->authorizations->expects($this->once())
            ->method('can')
            ->with('switch_upstream')
            ->willReturn(false);
        $this->site->expects($this->never())
            ->method('getUpstream');
        $this->site->expects($this->never())
            ->method('setUpstream');
        $this->setExpectedException(
            TerminusException::class,
            'You do not have permission to change the upstream of this site.'
        );

        $out = $this->command->set($site_name, $upstream_id);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:upstream:set command when the site being set did not have a valid previous upstream
     */
    public function testSetNoPreviousUpstream()
    {
        $site_name = 'my-site';
        $upstream_id = $this->upstream_data['id'];
        $this->site_upstream->id = null;

        $this->expectGetUpstream($upstream_id);

        $this->authorizations->expects($this->once())
            ->method('can')
            ->with('switch_upstream')
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('getUpstream')
            ->with()
            ->willReturn($this->site_upstream);

        $this->site
            ->method('getName')
            ->willReturn($site_name);

        $this->expectConfirmation();
        $this->site->expects($this->once())
            ->method('setUpstream')
            ->with($upstream_id)
            ->willReturn($this->workflow);

        $this->logger->expects($this->once())
            ->method('log')->with(
                $this->equalTo('notice'),
                $this->equalTo('Set upstream for {site} to {upstream}'),
                $this->equalTo(['site' => $site_name, 'upstream' => $this->upstream_data['label'],])
            );

        $out = $this->command->set($site_name, $upstream_id);
        $this->assertNull($out);
    }

    /**
     * Exercises the site:upstream:set command when the requested upstream cannot be found
     */
    public function testSetUpstreamDNE()
    {
        $site_name = 'my-site';
        $upstream_id = $this->upstream_data['id'];
        $exception_message = 'Error message';

        $this->expectGetUpstreams();

        $this->authorizations->expects($this->once())
            ->method('can')
            ->with('switch_upstream')
            ->willReturn(true);
        $this->upstreams->expects($this->once())
            ->method('get')
            ->with($upstream_id)
            ->will($this->throwException(new \Exception($exception_message)));
        $this->logger->expects($this->never())
            ->method('log');
        $this->site->expects($this->never())
            ->method('setUpstream');

        $this->setExpectedException(\Exception::class, $exception_message);

        $out = $this->command->set($site_name, $upstream_id);
        $this->assertNull($out);
    }

    /**
     * @param $upstream_id
     * @return Upstream
     */
    protected function expectGetUpstream($upstream_id)
    {
        $this->expectGetUpstreams();
        $this->upstreams->expects($this->once())
            ->method('get')
            ->with($upstream_id)
            ->willReturn($this->upstream);
        $this->upstream->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn($this->upstream_data['label']);
        $this->upstream->id = $this->upstream_data['id'];
        return $this->upstream;
    }

    protected function expectGetUpstreams()
    {
        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getUpstreams')
            ->with()
            ->willReturn($this->upstreams);
    }
}
