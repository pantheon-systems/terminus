<?php

namespace Pantheon\Terminus\Models;

/**
 * Class PaymentMethod
 * @package Pantheon\Terminus\Models
 */
class PaymentMethod extends TerminusModel
{
    public static $pretty_name = 'payment method';

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return ['id' => $this->id, 'label' => $this->get('label'),];
    }
}
