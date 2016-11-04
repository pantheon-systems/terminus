<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Removes the applied paynment method to the given site
     *
     * @authorized
     *
     * @command payment-method:remove
     * @aliases pm:remove, pm:rm
     *
     * @param string $site_name The name or UUID of the site to remove the payment method from
     *
     * @usage terminus payment-method:remove <site>
     *   Removes the set payment instrument from the <site> site, if one exists.
     */
    public function remove($site_name)
    {
        $site = $this->getSite($site_name);
        $site->removeInstrument()->wait();
        $this->log()->notice(
            'The payment method for the {site} site has been removed.',
            ['site' => $site->get('name'),]
        );
    }
}
