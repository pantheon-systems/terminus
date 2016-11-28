<?php


namespace Pantheon\Terminus\UnitTests\Models;

use Behat\Testwork\Environment\Environment;
use Pantheon\Terminus\Collections\Workflows;
use Pantheon\Terminus\Models\Hostname;
use Pantheon\Terminus\Models\Workflow;

class HostnameTest extends ModelTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->hostname = $this->_createHostname(['id' => 'dev.example.com']);
    }

    protected function _createHostname($attr)
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

        $hostname = new Hostname((object)$attr, ['collection' => (object)['environment' => $this->environment]]);

        $hostname->setRequest($this->request);
        return $hostname;
    }

    public function testDelete()
    {
        $this->request->expects($this->once())
            ->method('request')
            ->with('sites/abc/environments/dev/hostnames/dev.example.com', ['method' => 'delete']);

        $this->hostname->delete();
    }
    
    public function testSerialize()
    {
        $data = [
            'dns_zone_name' => 'pantheonsite.io',
            'environment' => 'live',
            'site_id' => '1111-1111-1111-1111-1111',
            'type' => 'platform',
            'id' => 'live-mysite.pantheonsite.io',
            'key' => 'live-mysite.pantheonsite.io',
            'deletable' => false,
        ];
        $hostname = $this->_createHostname($data);

        $expected = [
            'domain' => 'live-mysite.pantheonsite.io',
            'dns_zone_name' => 'pantheonsite.io',
            'environment' => 'live',
            'site_id' => '1111-1111-1111-1111-1111',
            'key' =>  'live-mysite.pantheonsite.io',
            'deletable' => false,
        ];
        $actual = $hostname->serialize();
        $this->assertEquals($expected, $actual);
    }
}
