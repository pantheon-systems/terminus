<?php

namespace Pantheon\Terminus\UnitTests\Collections;

/**
 * Class PaymentMethodsTest
 * Testing class for Pantheon\Terminus\Collections\PaymentMethods
 * @package Pantheon\Terminus\UnitTests\Collections
 */
class PaymentMethodsTest extends UserOwnedCollectionTest
{
    protected $url = 'users/USERID/instruments';
    protected $class = 'Pantheon\Terminus\Collections\PaymentMethods';
}
