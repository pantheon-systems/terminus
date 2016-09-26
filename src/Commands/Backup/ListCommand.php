<?php
/**
 * @file
 * Contains Pantheon\Terminus\Commands\SSHKey\ListCommand
 */


namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Terminus\Collections\Sites;
use Terminus\Models\Environment;

class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Lists the Backups for a given Site and Environment
     *
     * @authorized
     *
     * @command backup:list
     * @aliases backups
     *
     * @param string $site_env Site & environment to deploy to, in the form `site-name.env`.
*    * @param string $element [code|files|database|db] Only show backups of a certain type
     * @param array $options [format=<table|csv|yaml|json>]
     *
     * @return RowsOfFields
     *
     * @field-labels
     *   file: Filename
     *   size: Size
     *   date: Date
     *   initiator: Initiator
     *
     * @example terminus backup:list awesome-site.dev database --format=json
     *
     */
    public function listBackups(
        $site_env,
        $element = 'all',
        $options = ['format' => 'table']
    ) {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');

        switch ($element) {
            case 'all':
                $backup_element = null;
                break;
            case 'db':
                $backup_element = 'database';
                break;
            default:
                $backup_element = $element;
        }

        $backups = $env->backups->getFinishedBackups($backup_element);
        $data = [];
        foreach ($backups as $id => $backup) {
            $data[] = [
                'file'      => $backup->get('filename'),
                'size'      => $backup->getSizeInMb(),
                'date'      => $backup->getDate(),
                'initiator' => $backup->getInitiator(),
            ];
        }

        // Return the output data.
        return new RowsOfFields($data);
    }
}
