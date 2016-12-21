<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Remove the applied paynment method to the given site
     *
     * @authorize
     *
     * @command payment-method:remove
     * @aliases pm:remove pm:rm
     *
     * @param string $site_name The name or UUID of the site to remove the payment method from
     *
     * @usage <site>
     *   Removes the set payment method from the <site> site, if one exists.
     */
    public function remove($site_name)
    {
        $site = $this->getSite($site_name);
        $site->removePaymentMethod()->wait();
        $this->log()->notice(
            'The payment method for the {site} site has been removed.',
            ['site' => $site->get('name'),]
        );
    }
}
