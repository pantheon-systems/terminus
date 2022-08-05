<?php

namespace Pantheon\Terminus\Models;

use Pantheon\Terminus\Friends\EnvironmentInterface;
use Pantheon\Terminus\Friends\EnvironmentTrait;
use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class BackupSet
 * @package Pantheon\Terminus\Models
 */
class BackupSet extends TerminusModel implements EnvironmentInterface
{
    use EnvironmentTrait;

    const PRETTY_NAME = 'backup_set';

    /**
     * @var array
     */
    public static $date_attributes = ['timestamp',];

    public function getArchivesURL(): array
    {
        $archive_urls = [];
        if (!empty($this->attributes->items)) {
            foreach ($this->attributes->items as $item) {
                $archive_urls[] = $item->getArchiveURL();
            }
        }
        return $archive_urls;
    }

    public function serialize(): array
    {
        $data = [];
        if (!empty($this->attributes->items)) {
            foreach ($this->attributes->items as $item) {
                $data[] = $item->serialize();
            }
        }
        return $data;
    }

}
