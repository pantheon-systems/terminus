<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\PaymentMethod;

/**
 * Class PaymentMethods
 * @package Pantheon\Terminus\Collections
 */
class PaymentMethods extends UserOwnedCollection
{
    public static $pretty_name = 'payment methods';
    /**
     * @var string
     */
    protected $collected_class = PaymentMethod::class;
    /**
     * @var string
     */
    protected $url = 'users/{user_id}/instruments';

    /**
     * Retrieves a payment method object by either its UUID or its label
     *
     * @param string $id The identifier for the payment method requested
     * @return PaymentMethod
     * @throws TerminusException When there is more than one matching payment method
     * @throws TerminusNotFoundException When there are no matching payment methods
     */
    public function get($id)
    {
        $payment_methods = $this->all();
        if (isset($payment_methods[$id])) {
            return $payment_methods[$id];
        }
        $matches = array_filter($payment_methods, function ($payment_method) use ($id) {
            return ($payment_method->get('label') == $id);
        });
        if (empty($matches)) {
            throw new TerminusNotFoundException(
                'Could not locate a payment method identified by {id} on this account.',
                compact('id')
            );
        } else if (count($matches) > 1) {
            throw new TerminusException('More than one payment method matched {id}.', compact('id'));
        }
        return array_shift($matches);
    }
}
