<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class TagCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class TagCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\Tag\AddCommand
     * @covers \Pantheon\Terminus\Commands\Tag\ListCommand
     * @covers \Pantheon\Terminus\Commands\Tag\RemoveCommand
     *
     * @group tag
     * @gropu long
     */
    public function testSolrEnableDisable()
    {
        $this->fail("To Be Written");
    }
}
