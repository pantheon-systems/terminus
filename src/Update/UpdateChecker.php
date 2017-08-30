<?php

namespace Pantheon\Terminus\Update;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;

/**
 * Class UpdateChecker
 * @package Pantheon\Terminus\Update
 */
class UpdateChecker implements ConfigAwareInterface, ContainerAwareInterface, DataStoreAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use ContainerAwareTrait;
    use DataStoreAwareTrait;
    use LoggerAwareTrait;

    const DEFAULT_COLOR = "\e[0m";
    const UPDATE_COMMAND = 'curl -O https://raw.githubusercontent.com/pantheon-systems/terminus-installer/master/builds/installer.phar && php installer.phar update';
    const UPDATE_NOTICE = <<<EOT
A new Terminus version v{latest_version} is available.
You are currently using version v{running_version}. 
You can update Terminus by running `composer update` or using the Terminus installer:
{update_command}
EOT;
    const UPDATE_NOTICE_COLOR = "\e[38;5;33m";
    const UPDATE_VARS_COLOR = "\e[38;5;45m";

    /**
     * @param DataStoreInterface $data_store
     */
    public function __construct(DataStoreInterface $data_store)
    {
        $this->setDataStore($data_store);
    }

    public function run()
    {
        $running_version = $this->getRunningVersion();
        try {
            $latest_version = $this->getContainer()->get(LatestRelease::class, [$this->getDataStore(),])->get('version');
        } catch (TerminusNotFoundException $e) {
            $this->logger->debug('Terminus has no saved release information.');
            return;
        }

        $update_exists = version_compare($latest_version, $running_version, '>');
        $should_hide_update = (bool) $this->getConfig()->get('hide_update_message');
        if ($update_exists && !$should_hide_update) {
            $this->logger->notice($this->getUpdateNotice(), [
                'latest_version' => self::UPDATE_VARS_COLOR . $latest_version,
                'running_version' => self::UPDATE_VARS_COLOR . $running_version,
                'update_command' => self::UPDATE_VARS_COLOR . self::UPDATE_COMMAND,
            ]);
        }
    }

    /**
     * Retrieves the version number of the running Terminus instance
     *
     * @return string
     */
    private function getRunningVersion()
    {
        return $this->getConfig()->get('version');
    }

    /**
     * Returns a colorized update notice
     *
     * @return string
     */
    private function getUpdateNotice()
    {
        return self::UPDATE_NOTICE_COLOR
            . str_replace('}', '}' . self::UPDATE_NOTICE_COLOR, self::UPDATE_NOTICE)
            . self::DEFAULT_COLOR;
    }
}
