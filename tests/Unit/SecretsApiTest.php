<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\SecretsApi\SecretsApi;
use Pantheon\Terminus\Request\Request;
use Consolidation\Config\Config;

class SecretsApiTest extends TestCase
{
    private function invokeGetBaseURI(SecretsApi $api): string
    {
        $method = new \ReflectionMethod(SecretsApi::class, 'getBaseURI');
        $method->setAccessible(true);
        return $method->invoke($api);
    }

    private function createSecretsApi(array $configValues): SecretsApi
    {
        $request = new Request();
        $request->setConfig(new Config($configValues));
        $api = new SecretsApi();
        $api->setRequest($request);
        return $api;
    }

    public function testGetBaseURIWithPapiValues()
    {
        $api = $this->createSecretsApi([
            'papi_protocol' => 'http',
            'papi_port' => '8080',
            'papi_host' => 'localhost',
            'protocol' => 'https',
            'port' => '443',
            'host' => 'hermes.sandbox-example.com',
        ]);

        $this->assertEquals(
            'http://localhost:8080/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }

    public function testGetBaseURIWithProtocolAndPortFallback()
    {
        $api = $this->createSecretsApi([
            'protocol' => 'http',
            'port' => '9090',
            'papi_host' => 'my-api-host',
        ]);

        $this->assertEquals(
            'http://my-api-host:9090/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }

    public function testGetBaseURIWithHermesSandboxHost()
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'hermes.sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://pantheonapi.sandbox-host.example.com:443/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }

    public function testGetBaseURIWithNonHermesSandboxHost()
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://sandbox-host.example.com:443/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }

    public function testGetBaseURIWithDefaultHost()
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'terminus.pantheon.io',
        ]);

        $this->assertEquals(
            'https://terminus.pantheon.io:443/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }

    public function testGetBaseURIPapiHostTakesPrecedenceOverSandbox()
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'papi_host' => 'custom-papi-host.example.com',
            'host' => 'hermes.sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://custom-papi-host.example.com:443/customer-secrets/v1',
            $this->invokeGetBaseURI($api)
        );
    }
}
