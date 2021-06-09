<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use PHPUnit\Framework\TestCase;

class MultiDevTest extends TestCase
{
    use TerminusTestTrait;
    use SiteBaseSetupTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\CreateCommand
     * @group multidev
     * @group long
     */
    public function testMultidevCreateCommand()
    {
        $this->fail("Long test. To be written/run last");
    }

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\ListCommand
     * @group multidev
     * @group long
     */
    public function testMultidevListCommand()
    {
        $this->fail("To be written/run last");
    }

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\MergeFromDevCommand
     * @group multidev
     * @group long
     */
    public function testMultidevMergeFromDevCommand()
    {
        $this->fail("To be written/run last");
    }

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\MergeToDevCommand
     * @group multidev
     * @group long
     */
    public function testMultidevMergeToDevCommand()
    {
        $this->fail(" To be written/run last");
    }

    /**
     * @test
     * @covers Pantheon\Terminus\Commands\Multidev\DeleteCommand
     * @group multidev
     * @group long
     */
    public function testMultidevDeleteCommand()
    {
        $this->fail("To be written/run last");
    }
}
