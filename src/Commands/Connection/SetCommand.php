<?php

namespace Pantheon\Terminus\Commands\Connection;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
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
    use WorkflowProcessingTrait;

    const COMMIT_ADVICE = 'If you wish to save these changes, use `terminus env:commit {site_env}`.';
    const UNCOMMITTED_CHANGE_WARNING = 'This environment has uncommitted changes. Switching the connection mode will discard this work.';

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
        if ($env->hasUncommittedChanges()) {
            $this->log()->warning(
                self::UNCOMMITTED_CHANGE_WARNING . ' ' . self::COMMIT_ADVICE,
                compact('site_env')
            );
            if (!$this->confirm(
                'Are you sure you want to change the connection mode of {env}?',
                ['env' => $env->id,]
            )) {
                return;
            }
        }

        try {
            $mode = strtolower($mode);
            $workflow = $env->changeConnectionMode($mode);
        } catch (TerminusException $e) {
            $message = $e->getMessage();
            if (strpos($message, $mode) !== false) {
                $this->log()->notice($message);
                return;
            }
            throw $e;
        }

        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
