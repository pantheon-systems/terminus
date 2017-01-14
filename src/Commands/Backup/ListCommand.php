<?php

namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class ListCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Lists backups for a specific site and environment.
     *
     * @authorize
     *
     * @command backup:list
     * @aliases backups
     *
     * @field-labels
     *     file: Filename
     *     size: Size
     *     date: Date
     *     initiator: Initiator
     * @return RowsOfFields
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @param string $element [code|files|database|db] Backup element filter
     *
     * @usage terminus backup:list <site>.<env>
     *     Lists all backups made of <site>'s <env> environment.
     * @usage terminus backup:list <site>.<env> --element=<element>
     *     Lists all <element> backups made of <site>'s <env> environment.
     */
    public function listBackups($site_env, $element = 'all')
    {
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

        $backups = $env->getBackups()->getFinishedBackups($backup_element);

        $data = [];
        foreach ($backups as $backup) {
            $data[] = $backup->serialize();
        }

        // Return the output data.
        return new RowsOfFields($data);
    }
}
