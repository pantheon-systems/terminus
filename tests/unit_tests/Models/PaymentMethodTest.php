<?php

namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Models\PaymentMethod;

/**
 * Class PaymentMethodTest
 * Testing class for Pantheon\Terminus\Models\PaymentMethod
 * @package Pantheon\Terminus\UnitTests\Models
 */
class PaymentMethodTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the PaymentMethod::serialize() function
     */
    public function testSerialize()
    {
        $data = $expected = [
            'id' => 'payment method uuid',
            'label' => 'HamEx - 1111',
        ];
        $data['some_other_crap'] = 'a value';
        $payment_method = new PaymentMethod((object)$data);
        $this->assertEquals($expected, $payment_method->serialize());
    }
}
