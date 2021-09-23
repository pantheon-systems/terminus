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
     * @option string $site_env Site & environment in the format `site-name.env`
     * @option boolean $print Print URL only
     *
     * @param null $site_env
     * @param false[] $options
     *
     * @return string|null
     *
     * @usage Opens browser to user's account on the Pantheon Dashboard.
     * @usage --print Prints the URL for user's account on the Pantheon Dashboard.
     * @usage <site> Opens browser to <site> on the Pantheon Dashboard.
     * @usage <site>.<env> Opens browser to <site>'s <env> environment on the Pantheon Dashboard.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function view($site_env = null, $options = ['print' => false,])
    {
        if (!$site_env) {
            $url = $this->session()->getUser()->dashboardUrl();
        } elseif ($env = $this->getOptionalEnv($site_env)) {
            $url = $env->dashboardUrl();
        } else {
            $url = $this->getSite($site_env)->dashboardUrl();
        }

        if ($options['print']) {
            return $url;
        }
        $this->getContainer()->get(LocalMachineHelper::class)->openUrl($url);

        return null;
    }
}
