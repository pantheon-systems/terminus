<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class RemoveCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Disassociates the active payment method from a site.
     *
     * @authorize
     *
     * @command payment-method:remove
     * @aliases pm:remove pm:rm
     *
     * @param string $site_name Site name
     *
     * @usage <site> Disassociates the active payment method from <site>.
     */
    public function remove($site_name)
    {
        $site = $this->getSite($site_name);
        $this->processWorkflow($site->removePaymentMethod());
        $this->log()->notice(
            'The payment method for the {site} site has been removed.',
            ['site' => $site->get('name'),]
        );
    }
}
