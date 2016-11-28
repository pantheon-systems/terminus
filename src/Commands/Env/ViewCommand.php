<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

class ViewCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Print the URL for an environment or open it in a browser
     *
     * @authorize
     *
     * @command env:view
     * @aliases site:view
     *
     * @param string $site_env The site and environment to view in the form: <sitename>.<env>
     * @option boolean $print Output the URL only. Do not open the URL in the default browser.
     * @return string
     *
     * @usage: terminus env:view <site>.<env> --print
     *    Outputs the URL of the <env> environment of <site>
     * @usage: terminus env:view <site>.<env>
     *    Opens the URL of the <dev> environment of <site> in your default browser
     *
     * @throws TerminusException
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
