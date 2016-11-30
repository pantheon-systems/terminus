<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CloneContentCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CloneContentCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Clone content from one environment to another
     *
     * @authorize
     *
     * @command env:clone-content
     *
     * @param string $site_env The origin site/environment to clone content from
     * @param string $target_env The target environment to clone content to
     * @param array $options
     * @option bool $db-only Use to only clone the database
     * @option bool $files-only Use to only clone the files
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage terminus env:clone-content <site>.<env> <other_env>
     *   Clones the files and database from the <env> environment of <site> to its <other_env> environment
     * @usage terminus env:clone-content <site>.<env> <other_env> --db-only
     *   Clones the database from the <env> environment of <site> to its <other_env> environment
     * @usage terminus env:clone-content <site>.<env> <other_env> --files-only
     *   Clones the files from the <env> environment of <site> to its <other_env> environment
     */
    public function cloneContent($site_env, $target_env, array $options = ['db-only' => false, 'files-only' => false,])
    {
        if (!empty($options['db-only']) && !empty($options['files-only'])) {
            throw new TerminusException("You cannot specify both --db-only and --files-only");
        }

        list($site, $env) = $this->getSiteEnv($site_env);
        $from_name = $env->getName();
        $target = $site->getEnvironments()->get($target_env);

        if (empty($options['db-only'])) {
            $workflow = $target->cloneFiles($from_name);
            $this->log()->notice(
                "Cloning files from {from_name} environment to {target_env} environment",
                compact(['from_name', 'target_env'])
            );
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        }

        if (empty($options['files-only'])) {
            $workflow = $target->cloneDatabase($from_name);
            $this->log()->notice(
                "Cloning database from {from_name} environment to {target_env} environment",
                compact(['from_name', 'target_env'])
            );
            while (!$workflow->checkProgress()) {
                // @TODO: Add Symfony progress bar to indicate that something is happening.
            }
            $this->log()->notice($workflow->getMessage());
        }
    }
}
