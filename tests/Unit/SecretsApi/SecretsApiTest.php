<?php

declare(strict_types=1);

namespace Pantheon\Terminus\Tests\Unit\SecretsApi;

use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\SecretsApi\SecretsApi;
use Pantheon\Terminus\Tests\Unit\UnitTestCase;

class SecretsApiTest extends UnitTestCase
{
    private function createSecretsApi(array $configValues): SecretsApi
    {
        $config = (new TerminusConfig())->combine($configValues);
        $request = new Request();
        $request->setConfig($config);
        $api = new SecretsApi();
        $api->setRequest($request);
        return $api;
    }

    public function testGetBaseURIWithPapiValues(): void
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
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }

    public function testGetBaseURIWithProtocolAndPortFallback(): void
    {
        $api = $this->createSecretsApi([
            'protocol' => 'http',
            'port' => '9090',
            'papi_host' => 'my-api-host',
        ]);

        $this->assertEquals(
            'http://my-api-host:9090/customer-secrets/v1',
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }

    public function testGetBaseURIWithHermesSandboxHost(): void
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'hermes.sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://pantheonapi.sandbox-host.example.com:443/customer-secrets/v1',
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }

    public function testGetBaseURIWithNonHermesSandboxHost(): void
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://sandbox-host.example.com:443/customer-secrets/v1',
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }

    public function testGetBaseURIWithDefaultHost(): void
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'host' => 'terminus.pantheon.io',
        ]);

        $this->assertEquals(
            'https://terminus.pantheon.io:443/customer-secrets/v1',
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }

    public function testGetBaseURIPapiHostTakesPrecedenceOverSandbox(): void
    {
        $api = $this->createSecretsApi([
            'protocol' => 'https',
            'port' => '443',
            'papi_host' => 'custom-papi-host.example.com',
            'host' => 'hermes.sandbox-host.example.com',
        ]);

        $this->assertEquals(
            'https://custom-papi-host.example.com:443/customer-secrets/v1',
            $this->callPrivateMethod($api, 'getBaseURI')
        );
    }
}
