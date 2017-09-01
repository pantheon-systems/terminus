<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DatabaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

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
     *
     * @usage <site>.<env> <archive_url> Imports the database archive at <archive_url> to <site>'s <env> environment.
     */
    public function import($site_env, $url)
    {
        list($site, $env) = $this->getUnfrozenSiteEnv($site_env);

        $tr = ['site' => $site->getName(), 'env' => $env->getName()];
        if (!$this->confirm('Are you sure you overwrite the database for {env} on {site}?', $tr)) {
            return;
        }

        $workflow = $env->importDatabase($url);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            'Imported database to {site}.{env}.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
