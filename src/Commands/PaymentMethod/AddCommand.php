<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Applies a payment method attached to your account to the given site
     *
     * @authorized
     *
     * @command payment-method:add
     * @aliases pm:add
     *
     * @param string $site_name The name or UUID of the site to attach a paymnent method to
     * @param string $payment_method The label or UUID of the payment method to apply to the site
     *
     * @usage terminus payment-method:add <site> <method>
     *   Attaches the <method> payment method ot the <site> site
     */
    public function add($site_name, $payment_method)
    {
        $site = $this->getSite($site_name);
        $instrument = $this->session()->getUser()->getInstruments()->fetch()->get($payment_method);
        $site->addInstrument($instrument->id)->wait();
        $this->log()->notice(
            '{method} has been applied to the {site} site.',
            ['method' => $instrument->get('label'), 'site' => $site->get('name'),]
        );
    }
}
