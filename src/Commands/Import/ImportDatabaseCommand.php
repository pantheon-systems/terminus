<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class ImportDatabaseCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    /**
     * Imports a site archive onto a Pantheon site
     *
     * @authorized
     *
     * @command database
     * @aliases import:database
     *
     * @param string $sitename Name of the site to import to
     * @param string $url  URL at which the import archive exists
     * @usage terminus import:database <site_name> <archive_url>
     *   Imports the database in the archive URL to the named site.
     */
    public function importDatabase($sitename, $url)
    {
        $site = $sitename;
        list(, $env) = $this->getSiteEnv($site, 'dev');
        $workflow = $env->importDatabase($url);
        $workflow->wait();
        $this->log()->notice("Imported database into site.");
    }
}
