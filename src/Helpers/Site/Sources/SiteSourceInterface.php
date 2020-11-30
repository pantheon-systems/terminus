<?php

namespace Pantheon\D9ify\Site\Sources;

use Pantheon\D9ify\Site\InfoInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface SiteSourceInterface
 *
 * @package D9ify\Site\Sources
 */
interface SiteSourceInterface
{


    /**
     * @return bool
     */
    public function valid(): bool;

    /**
     * @param \D9ify\Site\Sources\OutputInterface $output
     *
     * @return bool
     */
    public function cloneFiles(OutputInterface $output): bool;

    /**
     * @return \D9ify\Site\InfoInterface
     */
    public function getSiteInfo(): InfoInterface;

    /**
     * @return array
     */
    public function getConnectionInfo(): array;
}
