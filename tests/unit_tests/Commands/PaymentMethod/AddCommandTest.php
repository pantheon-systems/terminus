<?php

namespace Pantheon\Terminus\UnitTests\Commands\PaymentMethod;

use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Commands\PaymentMethod\AddCommand;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\UnitTests\Commands\CommandTestCase;
use Pantheon\Terminus\UnitTests\Commands\WorkflowProgressTrait;

/**
 * Class AddCommandTest
 * Test suite for class for Pantheon\Terminus\Commands\PaymentMethod\AddCommand
 * @package Pantheon\Terminus\UnitTests\Commands\PaymentMethod
 */
class AddCommandTest extends CommandTestCase
{
    use WorkflowProgressTrait;

    /**
     * @var Session
     */
    protected $session;
    /**
     * @var User
     */
    protected $user;
    /**
     * @var PaymentMethod
     */
    protected $payment_method;
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
        $this->payment_method = $this->getMockBuilder(PaymentMethod::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->payment_method->id = 'payment_method_id';

        $this->session->expects($this->once())
            ->method('getUser')
            ->with()
            ->willReturn($this->user);
        $this->user->expects($this->once())
            ->method('getPaymentMethods')
            ->with()
            ->willReturn($this->payment_methods);
        $this->payment_methods->expects($this->once())
            ->method('fetch')
            ->with()
            ->willReturn($this->payment_methods);

        $this->command = new AddCommand($this->getConfig());
        $this->command->setContainer($this->getContainer());
        $this->command->setSession($this->session);
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);
        $this->expectWorkflowProcessing();
    }

    /**
     * Tests the payment-method:add command
     */
    public function testAdd()
    {
        $site_name = 'site_name';
        $payment_method_label = 'payment_method_label';

        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->payment_methods->expects($this->once())
            ->method('get')
            ->with($this->equalTo($payment_method_label))
            ->willReturn($this->payment_method);
        $this->site->expects($this->once())
            ->method('addPaymentMethod')
            ->with($this->equalTo($this->payment_method->id))
            ->willReturn($workflow);
        $this->payment_method->expects($this->once())
            ->method('get')
            ->with($this->equalTo('label'))
            ->willReturn($payment_method_label);
        $this->site->expects($this->once())
            ->method('get')
            ->with($this->equalTo('name'))
            ->willReturn($site_name);
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo('notice'),
                $this->equalTo('{method} has been applied to the {site} site.'),
                $this->equalTo(['method' => $payment_method_label, 'site' => $site_name,])
            );

        $out = $this->command->add($site_name, $payment_method_label);
        $this->assertNull($out);
    }
}
