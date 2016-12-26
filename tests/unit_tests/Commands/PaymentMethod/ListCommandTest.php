<?php

namespace Pantheon\Terminus\UnitTests\Commands\PaymentMethod;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Commands\PaymentMethod\ListCommand;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

/**
 * Class ListCommandTest
 * Testing class for Pantheon\Terminus\Commands\PaymentMethod\ListCommand
 * @package Pantheon\Terminus\UnitTests\Commands\PaymentMethod
 */
class ListCommandTest extends CommandTestCase
{
    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var PaymentMethods
     */
    protected $payment_methods;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->session = $this->getMockBuilder(Session::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->user = $this->getMockBuilder(User::class)
          ->disableOriginalConstructor()
          ->getMock();
        $this->payment_methods = $this->getMockBuilder(PaymentMethods::class)
          ->disableOriginalConstructor()
          ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getPaymentMethods')
            ->with()
            ->willReturn($this->payment_methods);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the payment-method:list command
     */
    public function testListPaymentMethods()
    {
        $data = ['payment_method_id' => ['id' => 'payment_method_id', 'label' => 'Card - 1111']];

        $this->payment_methods->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);

        $this->logger->expects($this->never())
            ->method('log');


        $out = $this->command->listPaymentMethods();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }


    /**
     * Tests the payment-method:list command when the list is empty
     */
    public function testListPaymentMethodsEmpty()
    {
        $this->payment_methods->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('There are no payment methods attached to this account.')
            );

        $out = $this->command->listPaymentMethods();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([], $out->getArrayCopy());
    }
}
