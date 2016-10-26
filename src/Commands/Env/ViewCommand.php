<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class ViewCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Output the URL for an environment or open it in a browser.
     *
     * @authorized
     *
     * @command env:view
     *
     * @param $site_env
     *  The site and environment to view in the form: <sitename>.<env>
     * @option print
     *  Output the URL only. Do not open the URL in the default browser.
     *
     * @usage: terminus env:view mysite.dev --print
     *  Output the URL of the environment dev of the site mysite
     * @usage: terminus env:view mysite.dev
     *  Open the URL of the environment dev of the site mysite in the default browser.

     * @return string
     * @throws \Terminus\Exceptions\TerminusException
     */
    public function view($site_env, $options = ['print' => false,])
    {
        list(, $env) = $this->getSiteEnv($site_env);

        $domain = $env->domain();
        $protocol = 'http';

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

        // Otherwise attempt to launch it.
        $cmd = '';
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
        if (!$cmd) {
            throw new TerminusException("Terminus is unable to open a browser on this OS");
        }
        $command = sprintf('%s %s', $cmd, $url);
        exec($command);
        return $url;
    }
}
