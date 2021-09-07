<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class HTTPSCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class HTTPSCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\HTTPS\InfoCommand
     *
     * @group https
     * @group short
     */
    public function testHttpsInfoCommand()
    {
        $httpsInfo = $this->terminusJsonResponse(sprintf('https:info %s.%s', $this->getSiteName(), 'dev'));
        $this->assertIsArray($httpsInfo);
        $this->assertNotEmpty($httpsInfo);
        $key = sprintf('%s-%s.pantheonsite.io', 'dev', $this->getSiteName());
        $this->assertArrayHasKey($key, $httpsInfo);

        $this->assertArrayHasKey('id', $httpsInfo[$key]);
        $this->assertEquals($key, $httpsInfo[$key]['id']);

        $this->assertArrayHasKey('type', $httpsInfo[$key]);
        $this->assertEquals('platform', $httpsInfo[$key]['type']);

        $this->assertArrayHasKey('status', $httpsInfo[$key]);
        $this->assertEquals('OK', $httpsInfo[$key]['status']);

        $this->assertArrayHasKey('status_message', $httpsInfo[$key]);
        $this->assertEquals('Launched', $httpsInfo[$key]['status_message']);

        $this->assertArrayHasKey('deletable', $httpsInfo[$key]);
        $this->assertFalse($httpsInfo[$key]['deletable']);
    }
}
