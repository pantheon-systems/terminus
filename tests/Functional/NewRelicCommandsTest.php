<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class NewRelicCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class NewRelicCommandsTest extends TestCase
{
    use TerminusTestTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\NewRelic\EnableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\DisableCommand
     * @covers \Pantheon\Terminus\Commands\NewRelic\InfoCommand
     *
     * @group new-relic
     * @group long
     */
    public function testNewRelicInfoEnableDisableCommands()
    {
        $newRelicInfo = $this->getNewRelicInfo();
        if ('active' === $newRelicInfo['state']) {
            $this->terminus(sprintf('new-relic:disable %s', $this->getSiteName()));
            $newRelicInfo = $this->getNewRelicInfo();
            $this->assertEmpty(array_filter($newRelicInfo));
        } else {
            $this->terminus(sprintf('new-relic:enable %s', $this->getSiteName()));
            $newRelicInfo = $this->getNewRelicInfo();
            $this->assertNotEmpty(array_filter($newRelicInfo));
            $this->assertEquals('active', $newRelicInfo['state']);
        }
    }

    /**
     * Returns the new relic info.
     *
     * @return array
     */
    protected function getNewRelicInfo(): array
    {
        $newRelicInfo = $this->terminusJsonResponse(sprintf('new-relic:info %s', $this->getSiteName()));
        $this->assertIsArray($newRelicInfo);
        $this->assertNotEmpty($newRelicInfo);
        $this->assertArrayHasKey('name', $newRelicInfo);
        $this->assertArrayHasKey('status', $newRelicInfo);
        $this->assertArrayHasKey('subscribed', $newRelicInfo);
        $this->assertArrayHasKey('state', $newRelicInfo);

        return $newRelicInfo;
    }
}
