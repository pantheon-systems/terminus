<?php

namespace Pantheon\Terminus\Commands\Dashboard;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ViewCommand
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
     * @option string $site_env Site & environment in the format `site-name.env`
     * @option boolean $print Print URL only
     *
     * @return string|null
     *
     * @usage Opens browser to user's account on the Pantheon Dashboard.
     * @usage --print Prints the URL for user's account on the Pantheon Dashboard.
     * @usage <site> Opens browser to <site> on the Pantheon Dashboard.
     * @usage <site>.<env> Opens browser to <site>'s <env> environment on the Pantheon Dashboard.
     */
    public function view($site_env = null, $options = ['print' => false,])
    {
        list($site, $env) = $this->getOptionalSiteEnv($site_env);
        $url = isset($site)
            ? isset($env) ? $env->dashboardUrl() : $site->dashboardUrl()
            : $this->session()->getUser()->dashboardUrl();
        if ($options['print']) {
            return $url;
        }
        $this->getContainer()->get(LocalMachineHelper::class)->openUrl($url);
    }
}
