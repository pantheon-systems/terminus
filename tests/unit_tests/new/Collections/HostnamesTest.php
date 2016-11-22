<?php


namespace Pantheon\Terminus\UnitTests\Collections;


use Pantheon\Terminus\Collections\Backups;
use Pantheon\Terminus\Collections\Hostnames;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Workflow;
use Terminus\Exceptions\TerminusException;

class HostnamesTest extends CollectionTestCase
{
    protected $environment;
    protected $hostnames;

    public function setUp()
    {
        parent::setUp();

        $this->hostnames = $this->_createHostnames();
    }

    protected function _createHostnames()
    {
        $this->workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflows = $this->getMockBuilder(Workflows::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment = $this->getMockBuilder(Environment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->environment->method('getWorkflows')->willReturn($this->workflows);

        $this->environment->site = (object)['id' => 'abc'];
        $this->environment->id = 'dev';

        $hostnames = new Hostnames(['environment' => $this->environment]);
        $hostnames->setRequest($this->request);
        $hostnames->setContainer($this->container);
        return $hostnames;
    }

    public function testCreate()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with('sites/abc/environments/dev/hostnames/dev.example.com', ['method' => 'put']);

        $this->hostnames->create('dev.example.com');
    }

    public function testSetHydration()
    {
        $this->hostnames->setHydration('test');
        $this->assertEquals('sites/abc/environments/dev/hostnames?hydrate=test', $this->hostnames->getUrl());
        $this->hostnames->setHydration('');
        $this->assertEquals('sites/abc/environments/dev/hostnames?hydrate=', $this->hostnames->getUrl());
    }
}

