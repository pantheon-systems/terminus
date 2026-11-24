<?php

namespace Pantheon\Terminus\Models;

/**
 * Class PaymentMethod
 *
 * @package Pantheon\Terminus\Models
 */
class PaymentMethod extends TerminusModel
{
    public const PRETTY_NAME = 'payment method';

    /**
     * @inheritdoc
     */
    public function serialize(): array
    {
        return ['id' => $this->id, 'label' => $this->get('label'),];
    }
}
