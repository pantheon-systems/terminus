<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class SiteCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class SiteCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Import a site archive onto a Pantheon site
     *
     * @authorize
     *
     * @command import:site
     * @aliases site:import import
     *
     * @option string $site Name of the site to import to
     * @option string $url URL at which the import archive exists
     *
     * @usage terminus import <site> <archive_url>
     *   Imports the file at <archive_url> to the site named <site>
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
