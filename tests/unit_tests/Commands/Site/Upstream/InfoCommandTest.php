<?php

namespace Pantheon\Terminus\UnitTests\Commands\Site\Upstream;

use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Commands\Site\Upstream\InfoCommand;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class InfoCommandTest
 * Test suite class for Pantheon\Terminus\Commands\Site\Upstream\InfoCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Site\Upstream
 */
class InfoCommandTest extends CommandTestCase
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var Upstream
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
    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site_upstream = $this->getMockBuilder(Upstream::class)
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
        $this->upstream_data = ['framework' => 'Framework', 'id' => 'upstream_id', 'longname' => 'Upstream Name',];
        $this->upstream->id = $this->site_upstream->id = $this->upstream_data['id'];

        $this->site->expects($this->once())
            ->method('getUpstream')
            ->with()
            ->willReturn($this->site_upstream);
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
            ->with($this->site_upstream->id)
            ->willReturn($this->upstream);

        $this->command = new InfoCommand($this->getConfig());
        $this->command->setSites($this->sites);
        $this->command->setSession($this->session);
    }

    /**
     * Exercises the site:upstream:info command
     */
    public function testInfo()
    {
        $this->upstream->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($this->upstream_data);

        $out = $this->command->info('site id');
        $this->assertInstanceOf(PropertyList::class, $out);
        $this->assertEquals($this->upstream_data, $out->getArrayCopy());
    }
}
