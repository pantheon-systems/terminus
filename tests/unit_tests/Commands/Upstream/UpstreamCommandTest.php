<?php

namespace Pantheon\Terminus\UnitTests\Commands\Upstream;

use Pantheon\Terminus\Collections\Upstreams;
use Pantheon\Terminus\Models\Organization;
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
     * @var Organization
     */
    protected $organization;
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
        $this->data = [
            'upstream_id' => ['framework' => 'backdrop', 'id' => 'upstream_id', 'label' => 'Upstream Name', 'type' => 'core', 'organization_id' => '',],
            'upstream_id2' => ['framework' => 'wordpress', 'id' => 'upstream_id2', 'label' => 'Name Upstream', 'type' => 'core', 'organization_id' => '',],
            'upstream_id3' => ['framework' => 'drupal', 'id' => 'upstream_id3', 'label' => 'Something Else', 'type' => 'project', 'organization_id' => '',],
            'upstream_id4' => ['framework' => 'drupal', 'id' => 'upstream_id4', 'label' => 'Not even', 'type' => 'custom', 'organization_id' => '',],
        ];
        $this->organization = $this->getMockBuilder(Organization::class)
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

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->method('getOrganizations')
            ->with()
            ->willReturn([$this->organization,]);
        $this->user->expects($this->any())
            ->method('getUpstreams')
            ->with()
            ->willReturn($this->upstreams);
        $this->upstream->expects($this->any())
            ->method('serialize')
            ->with()
            ->willReturn($this->data);
    }
}
