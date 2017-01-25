<?php

namespace Pantheon\Terminus\Commands\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Connection
 */
class SetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Sets Git or SFTP connection mode on a development environment (excludes Test and Live).
     *
     * @authorize
     *
     * @command connection:set
     *
     * @param string $site_env Site & development environment (excludes Test and Live) in the format `site-name.env`
     * @param string $mode [git|sftp] Connection mode
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> <mode> Sets the connection mode of <site>'s <env> environment to <mode>.
     */
    public function connectionSet($site_env, $mode)
    {
        list(, $env) = $this->getSiteEnv($site_env);

        if (in_array($env->id, ['test', 'live',])) {
            throw new TerminusException(
                'Connection mode cannot be set on the {env} environment',
                ['env' => $env->id,]
            );
        }

        $workflow = $env->changeConnectionMode($mode);
        if (is_string($workflow)) {
            $this->log()->notice($workflow);
        } else {
            while (!$workflow->checkProgress()) {
                // TODO: (ajbarry) Add workflow progress output
            }
            $this->log()->notice($workflow->getMessage());
        }
    }
}
