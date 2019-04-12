<?php

namespace Pantheon\Terminus\Commands\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class GetCommand
 * @package Pantheon\Terminus\Commands\Connection
 */
class GetCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Gets Git or SFTP connection mode on a development environment (excludes Test and Live).
     *
     * @authorize
     *
     * @command connection:get
     *
     * @param string $site_env Site & development environment (excludes Test and Live) in the format `site-name.env`
     *
     * @throws TerminusException
     *
     * @usage <site>.<env> gets the connection mode of <site>'s <env> environment.
     */
    public function connectionGet($site_env)
    {
        list(, $env) = $this->getSiteEnv($site_env);

        if (in_array($env->id, ['test', 'live',])) {
            throw new TerminusException(
                'Connection mode cannot be get on the {env} environment',
                ['env' => $env->id,]
            );
        }
        
        return $env->get('connection_mode');
    }
}
