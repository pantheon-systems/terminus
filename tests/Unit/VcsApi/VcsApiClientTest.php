<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\VcsApi;

use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;
use Pantheon\Terminus\VcsApi\Client;

class VcsApiClientTest extends UnitTestCase
{
    public function testGetPantheonApiBaseUriWithPapiProtocol(): void
    {
        $config = (new TerminusConfig())->combine(['papi_protocol' => 'http', 'papi_port' => '8080', 'papi_host' => 'localhost']);
        $request = new Request();
        $request->setConfig($config);
        $client = new Client($request, 1000);

        $this->assertEquals('http://localhost:8080/vcs/v1', $client->getPantheonApiBaseUri());
    }

    public function testGetPantheonApiBaseUriWithProtocolAndPort(): void
    {
        $config = (new TerminusConfig())->combine(['protocol' => 'http', 'port' => '8080', 'papi_host' => 'localhost']);
        $request = new Request();
        $request->setConfig($config);
        $client = new Client($request, 1000);

        $this->assertEquals('http://localhost:8080/vcs/v1', $client->getPantheonApiBaseUri());
    }

    public function testGetPantheonApiBaseUriWithDefaultValues(): void
    {
        $config = (new TerminusConfig())->combine(['papi_host' => 'localhost']);
        $request = new Request();
        $request->setConfig($config);
        $client = new Client($request, 1000);

        $this->assertEquals('https://localhost:443/vcs/v1', $client->getPantheonApiBaseUri());
    }
}
