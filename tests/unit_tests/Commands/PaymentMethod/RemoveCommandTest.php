<?php

namespace Pantheon\Terminus\UnitTests\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\PaymentMethod\RemoveCommand;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class RemoveCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\PaymentMethod\RemoveCommand
 * @package Pantheon\Terminus\UnitTests\Commands\PaymentMethod
 */
class RemoveCommandTest extends CommandTestCase
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->command = new RemoveCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the payment-method:remove command
     */
    public function testRemove()
    {
        $site_name = 'site_name';

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->site->expects($this->once())
            ->method('removePaymentMethod')
            ->with()
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('checkProgress')
            ->with()
            ->willReturn(true);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('The payment method for the {site} site has been removed.'),
                $this->equalTo(['site' => $site_name,])
            );

        $out = $this->command->remove($site_name);
        $this->assertNull($out);
    }
}
