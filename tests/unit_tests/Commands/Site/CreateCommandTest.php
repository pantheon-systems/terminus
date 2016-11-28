<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site;

use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Commands\Site\CreateCommand;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class CreateCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\CreateCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site
 * TODO: Update this when Org and Upstreams are both accessible through DI
 */
class CreateCommandTest extends CommandTestCase
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Upstream
     */
    protected $upstream;
    /**
     * @var Upstreams
     */
    protected $upstreams;
    /**
     * @var User
     */
    protected $user;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

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
        $this->upstream->id = 'upstream_id';

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getUpstreams')
            ->with()
            ->willReturn($this->upstreams);
        $this->upstreams->expects($this->once())
            ->method('get')
            ->willReturn($this->upstream);

        $this->command = new CreateCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setLogger($this->logger);
        $this->command->setSession($this->session);
    }

    /**
     * Exercises the site:create command
     */
    public function testCreate()
    {
        $site_name = 'site_name';
        $label = 'label';
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $workflow2 = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->expects($this->once())
            ->method('create')
            ->with($this->equalTo(['site_name' => $site_name, 'label' => $label,]))
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->logger->expects($this->at(0))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Creating a new site...')
            );

        $this->logger->expects($this->at(1))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deploying CMS...')
            );
        $this->site->expects($this->once())
            ->method('deployProduct')
            ->with($this->equalTo($this->upstream->id))
            ->willReturn($workflow2);
        $workflow2->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->logger->expects($this->at(2))
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('Deployed CMS')
            );

        $out = $this->command->create($site_name, $label, 'upstream');
        $this->assertNull($out);
    }
}
