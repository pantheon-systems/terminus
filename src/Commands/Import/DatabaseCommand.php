<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Commands\WorkflowProcessingTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class DatabaseCommand.
 *
 * @package Pantheon\Terminus\Commands\Import
 */
class DatabaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use WorkflowProcessingTrait;

    /**
     * Imports a database archive to the environment.
     *
     * @authorize
     *
     * @command import:database
     * @aliases import:db
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $url Publicly accessible URL of the database archive
     * @usage <site>.<env> <archive_url> Imports the database archive at <archive_url> to <site>'s <env> environment.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     */
    public function import($site_env, $url)
    {
        $this->requireSiteIsNotFrozen($site_env);
        $site = $this->getSite($site_env);
        $env = $this->getEnv($site_env);

        if (!$this->confirm(
            'Are you sure you overwrite the database for {env} on {site}?',
            ['site' => $site->getName(), 'env' => $env->getName()]
        )) {
            return;
        }

        $this->processWorkflow($env->importDatabase($url));
        $this->log()->notice(
            'Imported database to {site}.{env}.',
            ['site' => $site->getName(), 'env' => $env->getName()]
        );
    }
}
