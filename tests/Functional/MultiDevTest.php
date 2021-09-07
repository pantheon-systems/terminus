<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class ConnectionCommandsTest.
 *
 * @package Pantheon\Terminus\Tests\Functional
 *
 * @covers \Pantheon\Terminus\Commands\Multidev\CreateCommand
 *   Indirectly by creating a testing runtime multidev env in /tests/config/bootstrap.php
 * @covers \Pantheon\Terminus\Commands\Multidev\DeleteCommand
 *   Indirectly by deleting a testing runtime multidev env in /tests/config/bootstrap.php
 */
class MultiDevTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Multidev\ListCommand
     *
     * @group multidev
     * @group short
     */
    public function testMultidevListCommand()
    {
        $list = $this->terminusJsonResponse(sprintf('multidev:list %s', $this->getSiteName()));
        $this->assertIsArray($list);
        $this->assertNotEmpty($list);

        $envIds = array_column($list, 'id');
        $this->assertTrue(
            false !== array_search($this->getMdEnv(), $envIds),
            sprintf('Multidev "%s" should be in the list on multidev environments', $this->getMdEnv())
        );
    }
}
