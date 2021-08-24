<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class AliasesCommandTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class AliasesCommandTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\AliasesCommand
     *
     * @group todo
     */
    public function testGetAliases()
    {
        $this->fail("Figure out how to test");
        // Suggestions: get site list then make sure there's
        // an alias file for each one in the list?
    }
}
