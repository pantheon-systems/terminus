<?php

namespace Pantheon\Terminus\Commands\Import;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class FilesCommand
 * @package Pantheon\Terminus\Commands\Import
 */
class FilesCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Import a file archive into a Pantheon environment
     *
     * @authorize
     *
     * @command import:files
     *
     * @param string $site_env Site & environment to import files to, in the form `site-name.env`
     * @param string $url URL at which the import archive exists
     *
     * @usage terminus import:files <site>.<env> <archive_url>
     *   Imports the files in the archive at <archive_url> to the <env> environment of the <site> site
     */
    public function import($site_env, $url)
    {
        list($site, $env) = $this->getSiteEnv($site_env);
        $workflow = $env->importFiles($url);
        while (!$workflow->checkProgress()) {
            // @TODO: Add Symfony progress bar to indicate that something is happening.
        }
        $this->log()->notice(
            'Imported files to {site}.{env}.',
            ['site' => $site->get('name'), 'env' => $env->id,]
        );
    }
}
