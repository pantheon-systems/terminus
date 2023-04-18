<?php

namespace Pantheon\Terminus\Commands\Multidev;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class CreateCommand.
 *
 * @package Pantheon\Terminus\Commands\Multidev
 */
class CreateCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Creates a multidev environment.
     * If there is an existing Git branch with the multidev name,
     * then it will be used when the new environment is created.
     *
     * @authorize
     *
     * @command multidev:create
     * @aliases env:create
     *
     * @param string $site_env Site & source environment in the format `site-name.env`
     * @param string $multidev Multidev environment name
     * @param array $options
     * @option bool $no-db Do not clone database
     * @option bool $no-files Do not clone files
     *
     * @usage <site>.<env> <multidev> Creates the Multidev environment, <multidev>, within <site> with database and files from the <env> environment.
     * @usage <site>.<env> <multidev> --no-db Creates the <multidev> environment without database from the <env> environment.
     * @usage <site>.<env> <multidev> --no-files Creates the <multidev> environment without files from the <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function create(
        string $site_env,
        string $multidev,
        array $options = [
            'no-db' => false,
            'no-files' => false,
        ]
    ) {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->fetchSite($site_env);
        $env = $this->getEnv($site_env);

        if (strlen($multidev) > 11) {
            $multidev = substr($multidev, 0, 11);
            $this->output()->write(
                sprintf(
                    'Pantheon puts an 11 character limit on env names. Your name has been truncated to: %s',
                    $multidev
                )
            );
        }

        $workflow = $site->getEnvironments()->create($multidev, $env, $options);
        $this->processWorkflow($workflow);
        $this->log()->notice($workflow->getMessage());
    }
}
