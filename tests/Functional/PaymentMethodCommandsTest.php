<?php

namespace Pantheon\Terminus\Tests\Functional;

use Pantheon\Terminus\Tests\Traits\LoginHelperTrait;
use Pantheon\Terminus\Tests\Traits\TerminusTestTrait;
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
     */
    public function testPaymentMethodCommands()
    {
        $this->markTestSkipped('No easy way to mock a payment.');
    }
}
