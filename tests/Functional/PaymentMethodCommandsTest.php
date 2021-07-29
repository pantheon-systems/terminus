<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\SiteBaseSetupTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
use Pantheon\Terminus\Tests\Traits\UrlStatusCodeHelperTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class PaymentMethodCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PaymentMethodCommandsTest extends TestCase
{
    use TerminusTestTrait;
    use LoginHelperTrait;

    /**
     * @test
     * @covers \Pantheon\Terminus\Commands\PaymentMethod\AddCommand
     * @covers \Pantheon\Terminus\Commands\PaymentMethod\ListCommand
     * @covers \Pantheon\Terminus\Commands\PaymentMethod\RemoveCommand
     *
     * @group branch
     * @group long
     */
    public function testConnection()
    {
        $this->fail("To Be Written");
    }
}
