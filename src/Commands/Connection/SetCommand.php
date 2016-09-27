<?php

namespace Pantheon\Terminus\Commands\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

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
     * @authorized
     *
     * @command connection:set
     *
     * @param string $site_env_id Name of the environment to set. Note that you cannot change 'test' or 'live'.
     * @param string $mode Connection mode, one of: <git|sftp>
     *
     * @return bool
     *
     * @throws TerminusException
     */
    public function connectionSet($site_env_id, $mode)
    {
        list(, $env) = $this->getSiteEnv($site_env_id);

        if (in_array($env->id, ['test', 'live',])) {
            throw new TerminusException(
                'Connection mode cannot be set on the {env} environment',
                ['env' => $env->id,]
            );
        }

        $workflow = $env->changeConnectionMode($mode);
        if (is_string($workflow)) {
            $this->log()->notice($workflow);

            return false;
        } else {
            while (!$workflow->checkProgress()) {
                // TODO: (ajbarry) Add workflow progress output
            }
            $this->log()->notice($workflow->getMessage());
        }

        return true;
    }
}
