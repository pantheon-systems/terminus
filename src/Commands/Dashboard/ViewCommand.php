<?php

namespace Pantheon\Terminus\Commands\Dashboard;

use Pantheon\Terminus\Commands\TerminusCommand;
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
     * Print the URL to the Pantheon site dashboard or open it in a browser
     *
     * @authorize
     *
     * @command dashboard:view
     * @aliases dashboard
     *
     * @option string $site_env Site & environment to open the Dashboard to, in the form `site-name.env`
     * @option boolean $print Set to print out the Dashboard URL instead of opening it
     *
     * @return string|null
     *
     * @usage terminus dashboard
     *   Opens browser to the user's account on the Pantheon Dashboard
     * @usage terminus dashboard --print
     *   Prints the URL for the user's account on the Pantheon Dashboard
     * @usage terminus dashboard <site>
     *   Opens browser to the <site> on the Pantheon Dashboard
     * @usage terminus dashboard <site>.<env>
     *   Opens browser to <site>'s <env> environment on the Pantheon Dashboard
     */
    public function view($site_env = null, $options = ['print' => false,])
    {
        switch (php_uname('s')) {
            case 'Linux':
                $cmd = 'xdg-open';
                break;
            case 'Darwin':
                $cmd = 'open';
                break;
            case 'Windows NT':
                $cmd = 'start';
                break;
        }

        list($site, $env) = $this->getOptionalSiteEnv($site_env);
        $url = isset($site)
            ? isset($env) ? $env->dashboardUrl() : $site->dashboardUrl()
            : $this->session()->getUser()->dashboardUrl();
        if ($options['print']) {
            return $url;
        } else {
            $command = sprintf('%s %s', $cmd, $url);
            exec($command);
        }
    }
}
