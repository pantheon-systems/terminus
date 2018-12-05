<?php

namespace Pantheon\Terminus\Commands\Connection;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SetCommand
 * @package Pantheon\Terminus\Commands\Connection
 */
class SetCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
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
            $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
            $this->log()->notice($workflow->getMessage());
        }
    }
}
