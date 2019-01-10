<?php

namespace Pantheon\Terminus\Commands\Env;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\ProgressBars\WorkflowProgressBar;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CloneContentCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CloneContentCommand extends TerminusCommand implements ContainerAwareInterface, SiteAwareInterface
{
    use ContainerAwareTrait;
    use SiteAwareTrait;

    /**
     * @var Environment
     */
    private $source_env;
    /**
     * @var Environment
     */
    private $target_env;


    /**
     * Clones database/files from one environment to another environment.
     *
     * @authorize
     *
     * @command env:clone-content
     *
     * @param string $site_env Origin site & environment in the format `site-name.env`
     * @param string $target_env Target environment
     * @param array $options
     * @option bool $db-only Only clone database
     * @option bool $files-only Only clone files
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> <target_env> Clones database and files from <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --db-only Clones only the database from <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --files-only Clones only files from <site>'s <env> environment to <target_env> environment.
     */
    public function cloneContent($site_env, $target_env, array $options = ['db-only' => false, 'files-only' => false,])
    {
        if (!empty($options['db-only']) && !empty($options['files-only'])) {
            throw new TerminusException('You cannot specify both --db-only and --files-only');
        }

        list($site, $this->source_env) = $this->getUnfrozenSiteEnv($site_env);
        $this->target_env = $site->getEnvironments()->get($target_env);

        $this->checkForInitialization($this->source_env);
        $this->checkForInitialization($this->target_env);
        if (!$this->confirm(
            'Are you sure you want to clone content from {from} to {to} on {site}?',
            [
                'from' => $this->source_env->getName(),
                'site' => $site->getName(),
                'to' => $this->target_env->getName(),
            ]
        )) {
            return;
        }

        if (empty($options['db-only'])) {
            $this->cloneFiles();
        }

        if (empty($options['files-only'])) {
            $this->cloneDatabase();
        }
    }

    /**
     * Checks to see whether the indicated environment is initialized and stops the process if it isn't
     *
     * @param Environment $env
     * @throws TerminusExceptionCHecks Thrown if the passed-in environment is not initialized
     */
    private function checkForInitialization(Environment $env)
    {
        if (!$env->isInitialized()) {
            throw new TerminusException(
                "{site}'s {env} environment cannot be cloned because it has not been initialized. Please run `env:deploy {site}.{env}` to initialize it.",
                ['env' => $env->getName(), 'site' => $env->getSite()->getName(),]
            );
        }
    }

    /**
     * Emits the cloning notice and clones runs the database cloning
     */
    private function cloneDatabase()
    {
        $this->emitNotice('database');
        $this->runClone($this->target_env->cloneDatabase($this->source_env));
    }

    /**
     * Emits the cloning notice and clones runs the files cloning
     */
    private function cloneFiles()
    {
        $this->emitNotice('files');
        $this->runClone($this->target_env->cloneFiles($this->source_env));
    }

    /**
     * Emits the cloning notice
     *
     * @param string $element
     */
    private function emitNotice($element)
    {
        $this->log()->notice(
            "Cloning ${element} from {source} environment to {target} environment",
            ['source' => $this->source_env->getName(), 'target' => $this->target_env->getName(),]
        );
    }

    /**
     * Runs the clone workflow with a progress bar
     *
     * @param Workflow $workflow
     */
    private function runClone(Workflow $workflow)
    {
        $this->getContainer()->get(WorkflowProgressBar::class, [$this->output, $workflow,])->cycle();
        $this->log()->notice($workflow->getMessage());
    }
}
