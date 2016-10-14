<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Exceptions\TerminusException;

/**
 * Class ImportCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class ImportCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Imports a site archive onto a Pantheon site
     *
     * @authorized
     *
     * @command import
     * @aliases site:import
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
