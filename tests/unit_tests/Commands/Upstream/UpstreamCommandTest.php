<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Models\Upstream;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class UpstreamCommandTest
 * @package Pantheon\Terminus\UnitTests\Commands\Upstream
 */
abstract class UpstreamCommandTest extends CommandTestCase
{
    /**
     * @var string[]
     */
    protected $data;
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
    public function setUp()
    {
        $this->data = ['framework' => 'Framework', 'id' => 'upstream_id', 'name' => 'Upstream Name',];
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

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getUpstreams')
            ->with()
            ->willReturn($this->upstreams);
        $this->upstream->expects($this->any())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);
    }
}
