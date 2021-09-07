<?php

namespace Pantheon\Terminus\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * Class PaymentMethodCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PaymentMethodCommandsTest extends TestCase
{
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
