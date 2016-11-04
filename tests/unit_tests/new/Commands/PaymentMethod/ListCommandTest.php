<?php

namespace Pantheon\Terminus\UnitTests\Commands\PaymentMethod;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Collections\Instruments;
use Pantheon\Terminus\Commands\PaymentMethod\ListCommand;
use Pantheon\Terminus\Models\Instrument;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

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
     * @var Instruments
     */
    protected $instruments;

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
        $this->instruments = $this->getMockBuilder(Instruments::class)
          ->disableOriginalConstructor()
          ->getMock();

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getInstruments')
            ->with()
            ->willReturn($this->instruments);
        $this->instruments->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->instruments);

        $this->command = new ListCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
    }

    /**
     * Tests the payment-method:list command
     */
    public function testListPaymentMethods()
    {
        $data = ['id' => 'instrument_id', 'label' => 'Card - 1111',];

        $instrument = $this->getMockBuilder(Instrument::class)
            ->disableOriginalConstructor()
            ->getMock();
        $instrument->expects($this->once())
            ->method('serialize')
            ->with()
            ->willReturn($data);

        $this->instruments->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([$instrument,]);
        $this->logger->expects($this->never())
            ->method('log');


        $out = $this->command->listPaymentMethods();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([$data,], $out->getArrayCopy());
    }


    /**
     * Tests the payment-method:list command when the list is empty
     */
    public function testListPaymentMethodsEmpty()
    {
        $this->instruments->expects($this->once())
            ->method('all')
            ->with()
            ->willReturn([]);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('There are no instruments attached to this account.')
            );

        $out = $this->command->listPaymentMethods();
        $this->assertInstanceOf(RowsOfFields::class, $out);
        $this->assertEquals([], $out->getArrayCopy());
    }
}
