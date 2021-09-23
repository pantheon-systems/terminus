<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Helpers\LocalMachineHelper;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class ViewCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Displays the URL for the environment or opens the environment in a browser.
     *
     * @authorize
     *
     * @command env:view
     * @aliases site:view
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option boolean $print Print URL only
     * @return string|null
     *
     * @usage <site>.<env> Opens the browser to <site>'s <env> environment.
     * @usage <site>.<env> --print Prints the URL for <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function view($site_env, $options = ['print' => false,])
    {
        $this->requireSiteIsNotFrozen($site_env);
        $env = $this->getEnv($site_env);

        $domain = $env->domain();
        $protocol = 'https';

        if ($lock = $env->get('lock')) {
            if ($lock->locked) {
                $domain = $lock->username . ":" . $lock->password . '@' . $domain;
            }
        }
        $url = "$protocol://$domain/";

        // Return the URL if the user just wants to see it.
        if ($options['print']) {
            return $url;
        }
        $this->getContainer()->get(LocalMachineHelper::class)->openUrl($url);

        return null;
    }
}
