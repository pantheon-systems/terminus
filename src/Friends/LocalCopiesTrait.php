<?php

namespace Pantheon\Terminus\Friends;

use Pantheon\Terminus\Exceptions\TerminusException;

/**
 * Class LocalCopiesTrait.
 *
 * @package Pantheon\Terminus\Friends
 */
trait LocalCopiesTrait
{
    /**
     * Returns the path to the "local copies" directory.
     *
     * @return string
     *
     * @throws TerminusException
     */
    protected function getLocalCopiesDir(): string
    {
        $localCopies = $this->getConfig()->get('local_copies');

        return $this->createDirIfNotExists($localCopies);
    }

    /**
     * Returns the path to the database backups directory.
     *
     * @return string
     *
     * @throws TerminusException
     */
    protected function getLocalCopiesDbDir(): string
    {
        $dbDir = $this->getLocalCopiesDir() . DIRECTORY_SEPARATOR . 'db';

        return $this->createDirIfNotExists($dbDir);
    }

    /**
     * Returns the path to the files backups directory.
     *
     * @return string
     *
     * @throws TerminusException
     */
    protected function getLocalCopiesFilesDir(): string
    {
        $dbDir = $this->getLocalCopiesDir() . DIRECTORY_SEPARATOR . 'files';

        return $this->createDirIfNotExists($dbDir);
    }

    /**
     * Returns the path to the local site directory.
     *
     * @param string $site
     *   The site's name.
     * @return string
     *
     * @throws TerminusException
     */
    protected function getLocalCopiesSiteDir(string $site): string
    {
        $siteDir = $this->getLocalCopiesDir() . DIRECTORY_SEPARATOR . $site;

        return $this->createDirIfNotExists($siteDir);
    }

    /**
     * Creates the directory if not exists.
     *
     * @param $dir
     *   The directory to create.
     *
     * @return string
     *
     * @throws TerminusException
     */
    protected function createDirIfNotExists(string $dir): string
    {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw new TerminusException(
                    "Can't create directory {path}",
                    ['path' => $dir]
                );
            }
        }

        return $dir;
    }
}
