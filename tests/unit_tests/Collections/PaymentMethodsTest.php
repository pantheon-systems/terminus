<?php

namespace Pantheon\Terminus\UnitTests\Collections;

use Pantheon\Terminus\Collections\PaymentMethods;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\PaymentMethod;
use Pantheon\Terminus\Models\User;

/**
 * Class PaymentMethodsTest
 * Testing class for Pantheon\Terminus\Collections\PaymentMethods
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class PaymentMethodsTest extends UserOwnedCollectionTest
{
    /**
     * @var string
     */
    protected $class = PaymentMethods::class;
    /**
     * @var string
     */
    protected $url = 'users/USERID/instruments';

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->collection = $this->createPaymentMethods();
    }

    public function testGetByID()
    {
        $id = 'a';
        $model = $this->collection->get($id);
        $this->assertEquals($id, $model->id);
    }

    public function testGetByLabel()
    {
        $label = 'Visa - 2222';
        $model = $this->collection->get($label);
        $this->assertEquals($label, $model->get('label'));
    }

    public function testGetWhenNotFound()
    {
        $bad_id = 'invalid';
        $this->setExpectedException(
            TerminusNotFoundException::class,
            "Could not locate a payment method identified by $bad_id on this account."
        );
        $not_model = $this->collection->get($bad_id);
        $this->assertNull($not_model);
    }

    public function testGetWhenMultipleMatches()
    {
        $shared_label = 'Visa - 1111';
        $this->setExpectedException(
            TerminusException::class,
            "More than one payment method matched $shared_label."
        );
        $not_model = $this->collection->get($shared_label);
        $this->assertNull($not_model);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPaymentMethods()
    {
        $model_data = [
            'a' => (object)[
                'label' => 'Visa - 1111',
                'id' => 'a',
                'filename' => ''
            ],
            'b' => (object)[
                'label' => 'Visa - 2222',
                'id' => 'b',
            ],
            'c' => (object)[
                'label' => 'Visa - 1111',
                'id' => 'c',
            ],
        ];
        $user = new User((object)['id' => 'USERID',]);

        $models = [];
        foreach ($model_data as $id => $data) {
            $models[$id] = $this->getMockBuilder(PaymentMethod::class)
                ->setMethods(['get',])
                ->enableOriginalConstructor()
                ->setConstructorArgs([$data, ['user' => $user,],])
                ->getMock();
            $models[$id]->method('get')->with($this->equalTo('label'))->willReturn($data->label);
        }

        $methods = $this->getMockBuilder(PaymentMethods::class)
            ->setMethods(['all'])
            ->enableOriginalConstructor()
            ->setConstructorArgs([['user' => $user,],])
            ->getMock();
        $methods->expects($this->any())
            ->method('all')
            ->willReturn($models);
        return $methods;
    }
}
