<?php

namespace Pantheon\Terminus\Commands;

class ImportCommand extends TerminusCommand
{
    /**
     * @var boolean True if the command requires the user to be logged in
     */
    protected $authorized = true;

    /**
     * Imports a site archive onto a Pantheon site
     *
     * @name import
     * @alias site:import
     *
     * @option string $site Name of the site to import to
     * @option string $url  URL at which the import archive exists
     * @usage terminus import --site=<site_name> --url=<archive_url>
     *   Imports the file at the archive URL to the site named.
     */
    public function import(array $options = [])
    {
    }
}
