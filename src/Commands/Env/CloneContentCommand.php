<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\Environment;
use Pantheon\Terminus\Models\Workflow;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CloneContentCommand
 *
 * @package Pantheon\Terminus\Commands\Env
 */
class CloneContentCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

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
     * WordPress sites will search the database for the default domain and
     * replace it with the target environment's domain unless you specify
     * a different search/replace string with --from-url and --to-url.
     *
     * IMPORTANT NOTE: if you have a WordPress Multisite installation, you
     * will need an entry in your pantheon.yml in order to search all the
     * sites in your install.  See http://pantheon.io/some/link/in/docs.
     *
     * @authorize
     *
     * @command env:clone-content
     *
     * @param string $site_env Origin site & environment in the format
     *     `site-name.env`
     * @param string $target_env Target environment
     * @param array $options
     *
     * @option bool $cc Whether or not to clear caches
     * @option bool $db-only Only clone database
     * @option bool $files-only Only clone files
     * @option bool $updatedb Update the Drupal database
     * @option string $from-url URL to search for (WordPress only)
     * @option string $to-url URL to replace (WordPress only)
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     *
     * @usage <site>.<env> <target_env> Clones database and files from <site>'s
     *     <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --cc Clones from <site>'s <env>
     *     environment to <target_env> environment and clears the cache.
     * @usage <site>.<env> <target_env> --db-only Clones only the database from
     *     <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --files-only Clones only files from
     *     <site>'s <env> environment to <target_env> environment.
     * @usage <site>.<env> <target_env> --updatedb Clones from <site>'s <env>
     *     environment to <target_env> environment and updates the Drupal
     *     database (if applicable).
     * @usage <site>.<env> <target_env> --from-url=www.example.com
     *     --to-url=mulitidevenv.example.com (WordPress only) Clones from
     *     <site>'s <env> environment to <target_env> environment and replaces
     *     www.example.com with mulitidevenv.example.com in the database.
     */
    public function cloneContent(
        $site_env,
        $target_env,
        array $options = [
            'cc' => false,
            'db-only' => false,
            'files-only' => false,
            'updatedb' => false,
            'from-url' => '',
            'to-url' => '',
        ]
    ) {
        if (!empty($options['db-only']) && !empty($options['files-only'])) {
            throw new TerminusException(
                'You cannot specify both --db-only and --files-only'
            );
        }

        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSiteById($site_env);
        $this->source_env = $this->getEnv($site_env);
        $this->target_env = $site->getEnvironments()->get($target_env);

        if ($this->source_env->id === $target_env) {
            $this->log()->notice(
                'The clone has been skipped because the source and target environments are the same.'
            );
            return;
        }

        $this->checkForInitialization($this->source_env, 'from');
        $this->checkForInitialization($this->target_env, 'into');
        if (
            !$this->confirm(
                'Are you sure you want to clone content from {from} to {to} on {site}?',
                [
                    'from' => $this->source_env->getName(),
                    'site' => $site->getName(),
                    'to' => $this->target_env->getName(),
                ]
            )
        ) {
            return;
        }

        if (empty($options['db-only'])) {
            $this->cloneFiles();
        }

        if (empty($options['files-only'])) {
            $this->cloneDatabase($options);
        }
    }

    /**
     * Checks to see whether the indicated environment is initialized and stops
     * the process if it isn't
     *
     * @param Environment $env
     * @param string $direction "into" or "from" are recommended.
     *
     * @throws TerminusException Thrown if the passed-in environment is not
     *     initialized
     */
    private function checkForInitialization(Environment $env, $direction = '')
    {
        if (!$env->isInitialized()) {
            throw new TerminusException(
                "{site}'s {env} environment cannot be cloned {direction} because it has not been initialized. "
                . 'Please run `env:deploy {env}` to initialize it.',
                ['direction' => $direction, 'env' => $env]
            );
        }
    }

    /**
     * Emits the cloning notice and clones runs the database cloning
     *
     * @param array $options Options to be sent to the API
     *    boolean cc Whether or not to clear caches
     *    boolean updatedb Update the Drupal database
     */
    private function cloneDatabase(array $options)
    {
        $params = [
            'clear_cache' => $options['cc'],
            'updatedb' => $options['updatedb'],
        ];
        if ($options['from-url'] != '') {
            $params['from_url'] = $options['from-url'];
        }
        if ($options['to-url'] != '') {
            $params['to_url'] = $options['to-url'];
        }
        $this->emitNotice('database');
        $this->runClone(
            $this->target_env->cloneDatabase($this->source_env, $params)
        );
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
            "Cloning {$element} from {source} environment to {target} environment",
            [
                'source' => $this->source_env->getName(),
                'target' => $this->target_env->getName(),
            ]
        );
    }

    /**
     * Runs the clone workflow with a progress bar
     *
     * @param Workflow $workflow
     */
    private function runClone(Workflow $workflow)
    {
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
