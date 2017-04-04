<?php

namespace Pantheon\Terminus\Commands\Backup;

use Consolidation\OutputFormatters\StructuredData\PropertyList;

/**
 * Class InfoCommand
 * @package Pantheon\Terminus\Commands\Backup
 */
class InfoCommand extends SingleBackupCommand
{
    /**
     * Displays information about a specific backup or the latest backup.
     *
     * @authorize
     *
     * @command backup:info
     *
     * @field-labels
     *     file: Filename
     *     size: Size
     *     date: Date
     *     expiry: Expiry
     *     initiator: Initiator
     *     url: URL
     * @return PropertyList
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option string $file [filename.tgz] Name of backup file
     * @option string $element [all|code|files|database|db] Backup element to retrieve
     *
     * @usage <site>.<env> Displays information about the most recent backup of any type in <site>'s <env> environment.
     * @usage <site>.<env> --file=<file_name> Displays information about the backup with the file name <file_name> in <site>'s <env> environment.
     * @usage <site>.<env> --element=<element> Displays information about the most recent <element> backup in <site>'s <env> environment.
     */
    public function info($site_env, array $options = ['file' => null, 'element' => 'all',])
    {
        $backup = $this->getBackup($site_env, $options);
        return new PropertyList(array_merge($backup->serialize(), ['url' => $backup->getUrl(),]));
    }
}
