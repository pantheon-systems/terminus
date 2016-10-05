<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

class ImportCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    /**
     * Imports a site archive onto a Pantheon site
     *
     * @authorized
     *
     * @command import
     * @aliases site:import import:site
     *
     * @param string $sitename Name of the site to import to
     * @param string $url  URL at which the import archive exists
     * @usage terminus import <site_name> <archive_url>
     *   Imports the site in the archive URL to the named site.
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
