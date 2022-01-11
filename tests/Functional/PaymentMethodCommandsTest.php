<?php

namespace Pantheon\Terminus\Tests\Functional;

/**
 * Class PaymentMethodCommandsTest
 *
 * @package Pantheon\Terminus\Tests\Functional
 */
class PaymentMethodCommandsTest extends TerminusTestBase
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
