<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\VcsApi\Client;
use Pantheon\Terminus\Request\Request;
use Consolidation\Config\Config;

class VcsApiClientTest extends TestCase
{
    public function testGetPantheonApiBaseUriWithPapiProtocol()
    {
        $request = new Request();
        $request->setConfig(new Config(['papi_protocol' => 'http', 'papi_port' => '8080', 'papi_host' => 'localhost']));
        $client = new Client($request, 1000);

        $expected_uri = 'http://localhost:8080/vcs/v1';
        $this->assertEquals($expected_uri, $client->getPantheonApiBaseUri());
    }

    public function testGetPantheonApiBaseUriWithProtocolAndPort()
    {
        $request = new Request();
        $request->setConfig(new Config(['protocol' => 'http', 'port' => '8080', 'papi_host' => 'localhost']));
        $client = new Client($request, 1000);

        $expected_uri = 'http://localhost:8080/vcs/v1';
        $this->assertEquals($expected_uri, $client->getPantheonApiBaseUri());
    }

    public function testGetPantheonApiBaseUriWithDefaultValues()
    {
        $request = new Request();
        $request->setConfig(new Config(['papi_host' => 'localhost']));
        $client = new Client($request, 1000);

        $expected_uri = 'https://localhost:443/vcs/v1';
        $this->assertEquals($expected_uri, $client->getPantheonApiBaseUri());
    }
}
