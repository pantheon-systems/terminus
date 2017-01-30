<?php

namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

/**
 * Class ListCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class ListCommand extends BackupCommand
{
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
     * @param string $element [all|code|files|database|db] DEPRECATED Backup element filter
     * @option string $element [all|code|files|database|db] Backup element filter
     *
     * @usage <site>.<env> Lists all backups made of <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Lists all <element> backups made of <site>'s <env> environment.
     *
     * @deprecated 1.0.0 The element parameter is inconsistent with the other backup commands and will be removed. Please use the option instead.
     */
    public function listBackups($site_env, $element = 'all', array $options = ['element' => 'all',])
    {
        list(, $env) = $this->getSiteEnv($site_env, 'dev');
        // If the element option is set to default, it looks to the element parameter.
        $backup_element = ($options['element'] !== 'all') ? $options['element'] : $element;

        $data = array_map(
            function ($backup) {
                return $backup->serialize();
            },
            $env->getBackups()->getFinishedBackups($this->getElement($backup_element))
        );

        // Return the output data.
        return new RowsOfFields($data);
    }
}
