<?php

namespace Pantheon\Terminus\Commands\Site;

use Terminus\Exceptions\TerminusException;

/**
 * Class ImportCommand
 * @package Pantheon\Terminus\Commands\Site
 */
class ImportCommand extends SiteCommand
{
    /**
     * Imports a site archive onto a Pantheon site
     *
     * @authorized
     *
     * @command site:import
     * @aliases import
     *
     * @option string $site Name of the site to import to
     * @option string $url  URL at which the import archive exists
     * @usage terminus import <site_name> <archive_url>
     *   Imports the file at the archive URL to the site named.
     */
    public function import($sitename, $url)
    {
        $site = $sitename;
        list(, $env) = $this->getSiteEnv($site, 'dev');
        $workflow = $env->import($url);
        try {
            $workflow->wait();
        } catch (\Exception $e) {
            if ($e->getMessage() == 'Successfully queued import_site') {
                throw new TerminusException('Site import failed');
            }
            throw $e;
        }
        $this->log()->notice('Imported site onto Pantheon');
    }
}
