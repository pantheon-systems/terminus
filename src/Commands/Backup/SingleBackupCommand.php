<?php

namespace Pantheon\Terminus\Commands\Backup;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Exceptions\TerminusException;

abstract class SingleBackupCommand extends BackupCommand
{
    /**
     * @param $site_env
     * @param array $options
     *
     * @return Backup
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusNotFoundException
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    protected function getBackup($site_env, array $options = ['file' => null, 'element' => 'all',]): Backup
    {
        $env = $this->getEnv($site_env);

        if (isset($options['file']) && !is_null($file_name = $options['file'])) {
            $backup = $env->getBackups()->getBackupByFileName($file_name);
        } else {
            $element = isset($options['element']) ? $this->getElement($options['element']) : null;
            $backups = $env->getBackups()->getFinishedBackups($element);
            if (empty($backups)) {
                throw new TerminusNotFoundException(
                    'No backups available. Create one with `terminus backup:create {site}.{env}`',
                    [
                        'site' => $this->getSiteById($site_env)->getName(),
                        'env' => $env->getName(),
                    ]
                );
            }
            $backup = array_shift($backups);
        }
        return $backup;
    }

    /**
     * Validate the provided element name and throw exceptions if unsupported.
     *
     * @param string $site_env
     *   Site & environment in the format `site-name.env`
     * @param string $element
     *   The element name to validate.
     * @param bool $supportAll
     *   Whether to support the "all" element.
     *
     * @throws TerminusException If the element is not supported.
     */
    protected function validateElement(string $siteEnv, string $element, bool $supportAll = true): void
    {
        if ($element == 'all' && !$supportAll) {
            throw new TerminusException('The backup element "all" is not supported for this command.');
        }

        $env = $this->getEnv($siteEnv);
        $supported = $env->getBackups()->getValidElements();

        if ($supportAll) {
            $supported[] = 'all';
        }
        if (!in_array($element, $supported)) {
            throw new TerminusException(
                sprintf('Element should be one of the following items: %s', implode(', ', $supported))
            );
        }
    }
}
