<?php

namespace Pantheon\Terminus\Commands\Dashboard;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ViewCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Open the Pantheon site dashboard in a browser
     *
     * @command dashboard:view
     * @aliases dashboard
     *
     * @option string $site_env Site & environment to deploy to, in the form `site-name.env`
     * @option boolean $print Don't try to open the link, just output it
     *
     * @usage terminus dashboard
     *   Opens browser to user's account on Pantheon Dashboard
     * @usage terminus dashboard --print
     *   Prints url for user's account on Pantheon Dashboard
     * @usage terminus dashboard my-awesome-site
     *   Opens browser to site on Pantheon Dashboard
     * @usage terminus dashboard my-awesome-site.env-name
     *   Opens browser to specific site environment on Pantheon Dashboard
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
        if (isset($site)) {
            if (isset($env)) {
                $url = $env->dashboardUrl();
            } else {
                $url = $site->dashboardUrl();
            }
        } else {
            $url = $this->session()->getUser()->dashboardUrl();
        }

        if ($options['print']) {
            return $url;
        } else {
            $command = sprintf('%s %s', $cmd, $url);
            exec($command);
        }
    }
}
