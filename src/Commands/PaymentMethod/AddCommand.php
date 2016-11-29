<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class AddCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class AddCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Apply a payment method attached to your account to the given site
     *
     * @authorize
     *
     * @command payment-method:add
     * @aliases pm:add
     *
     * @param string $site_name The name or UUID of the site to attach a payment method to
     * @param string $payment_method The label or UUID of the payment method to apply to the site
     *
     * @usage terminus payment-method:add <site> <method>
     *   Attaches the <method> payment method ot the <site> site
     */
    public function add($site_name, $payment_method)
    {
        $site = $this->getSite($site_name);
        $pm = $this->session()->getUser()->getPaymentMethods()->fetch()->get($payment_method);
        $site->addPaymentMethod($pm->id)->wait();
        $this->log()->notice(
            '{method} has been applied to the {site} site.',
            ['method' => $pm->get('label'), 'site' => $site->get('name'),]
        );
    }
}
