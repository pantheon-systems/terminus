<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class ListCommand extends TerminusCommand
{
    /**
     * Displays the list of payment methods for the currently logged-in user.
     *
     * @authorize
     *
     * @command payment-method:list
     * @aliases payment-methods pm:list pms
     *
     * @field-labels
     *     label: Label
     *     id: ID
     * @return RowsOfFields
     *
     * @usage Displays the list of payment methods for the currently logged-in user.
     */
    public function listPaymentMethods()
    {
        $methods = $this->session()->getUser()->getPaymentMethods()->serialize();
        if (empty($methods)) {
            $this->log()->notice('There are no payment methods attached to this account.');
        }
        return new RowsOfFields($methods);
    }
}
