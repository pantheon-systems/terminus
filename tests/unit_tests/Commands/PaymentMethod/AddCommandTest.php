<?php

namespace Pantheon\Terminus\UnitTests\Commands\PaymentMethod;

use Pantheon\Terminus\Collections\Instruments;
use Pantheon\Terminus\Commands\PaymentMethod\AddCommand;
use Pantheon\Terminus\Models\Instrument;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;

class AddCommandTest extends CommandTestCase
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
     * @var Instrument
     */
    protected $instrument;
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
        $this->instrument = $this->getMockBuilder(Instrument::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->instrument->id = 'instrument_id';

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

        $this->command = new AddCommand($this->getConfig());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
    }

    /**
     * Tests the payment-method:add command
     */
    public function testAdd()
    {
        $site_name = 'site_name';
        $instrument_label = 'instrument_label';

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->instruments->expects($this->once())
            ->method('get')
            ->with($this->equalTo($instrument_label))
            ->willReturn($this->instrument);
        $this->site->expects($this->once())
            ->method('addInstrument')
            ->with($this->equalTo($this->instrument->id))
            ->willReturn($workflow);
        $workflow->expects($this->once())
            ->method('wait')
            ->with();
        $this->instrument->expects($this->once())
            ->method('get')
            ->with($this->equalTo('label'))
            ->willReturn($instrument_label);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{method} has been applied to the {site} site.'),
                $this->equalTo(['method' => $instrument_label, 'site' => $site_name,])
            );

        $out = $this->command->add($site_name, $instrument_label);
        $this->assertNull($out);
    }
}
