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
     * Lists the payment methods attached to the logged-in account
     *
     * @authorize
     *
     * @command payment-method:list
     * @aliases payment-methods pm:list pms
     *
     * @field-labels
     *   label: Label
     *   id: ID
     * @return RowsOfFields
     *
     * @usage terminus payment-method:list
     *   Display a list of payment methods which the logged-in user has attached to their account
     */
    public function listPaymentMethods()
    {
        $methods = array_map(
            function ($method) {
                return $method->serialize();
            },
            $this->session()->getUser()->getInstruments()->fetch()->all()
        );
        if (empty($methods)) {
            $this->log()->notice('There are no instruments attached to this account.');
        }
        return new RowsOfFields($methods);
    }
}
