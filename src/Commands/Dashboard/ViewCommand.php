<?php

namespace Pantheon\Terminus\Commands\Dashboard;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ViewCommand.
 *
 * @package Pantheon\Terminus\Commands\Dashboard
 */
class ViewCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the URL for the Pantheon Dashboard or opens the Dashboard in a browser.
     *
     * @authorize
     *
     * @command dashboard:view
     * @aliases dashboard
     *
     * @option boolean $print Print URL only
     *
     * @param string|null $site_env Site & environment in the format `site-name.env`
     * @param array $options
     *
     * @return string|null
     *
     * @usage Opens browser to user's account on the Pantheon Dashboard.
     * @usage --print Prints the URL for user's account on the Pantheon Dashboard.
     * @usage <site> Opens browser to <site> on the Pantheon Dashboard.
     * @usage <site>.<env> Opens browser to <site>'s <env> environment on the Pantheon Dashboard.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function view($site_env = null, array $options = ['print' => false])
    {
        if ($options['print']) {
            return $this->getDashboardUrl($site_env);
        }
        $this->getContainer()
            ->get(LocalMachineHelper::class)
            ->openUrl($this->getDashboardUrl($site_env));

        return null;
    }

    /**
     * Returns the dashboard URL.
     *
     * @param string|null $site_env
     *
     * @return string
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    private function getDashboardUrl(?string $site_env): string
    {
        if (null === $site_env) {
            return $this->session()->getUser()->dashboardUrl();
        }

        if ($this->getOptionalEnv($site_env)) {
            return $this->getOptionalEnv($site_env)->dashboardUrl();
        }

        return $this->getSite($site_env)->dashboardUrl();
    }
}
