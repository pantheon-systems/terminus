<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

class DatabaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Import a database archive into a Pantheon environment
     *
     * @authorize
     *
     * @command import:database
     * @aliases import:db
     *
     * @param string $site_env Site & environment to import a database to, in the form `site-name.env`
     * @param string $url URL at which the import archive exists
     *
     * @usage terminus import:database <site>.<env> <archive_url>
     *   Imports the database in the archive at <archive_url> to the <env> environment of the <site> site
     */
    public function import($site_env, $url)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
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
