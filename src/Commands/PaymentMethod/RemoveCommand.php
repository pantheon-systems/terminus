<?php

namespace Pantheon\Terminus\Commands\PaymentMethod;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class RemoveCommand
 * @package Pantheon\Terminus\Commands\PaymentMethod
 */
class RemoveCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

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
        $workflow = $site->removePaymentMethod();
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice(
            'The payment method for the {site} site has been removed.',
            ['site' => $site->get('name'),]
        );
    }
}
