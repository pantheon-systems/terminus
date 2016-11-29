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
     * Set git or sftp connection mode on a site's dev or multidev environment
     *
     * @authorize
     *
     * @command connection:set
     *
     * @param string $site_env Name of the environment to set. Note that you cannot change 'test' or 'live'.
     * @param string $mode [git|sftp] The connection mode to set
     *
     * @throws TerminusException
     *
     * @usage terminus connection:set <site>.<env> <mode>
     *    Sets the connection mode of the <env> environment of <site> to <mode>
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
