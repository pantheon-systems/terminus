<?php

namespace Pantheon\Terminus\Update;

use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreAwareInterface;
use Pantheon\Terminus\DataStore\DataStoreAwareTrait;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Request\RequestAwareInterface;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class LatestRelease
 * @package Pantheon\Terminus\Update
 */
class LatestRelease implements ContainerAwareInterface, DataStoreAwareInterface, LoggerAwareInterface, RequestAwareInterface
{
    use ContainerAwareTrait;
    use DataStoreAwareTrait;
    use LoggerAwareTrait;
    use RequestAwareTrait;

    /**
     * @var object
     */
    private $attributes;

    const SAVE_FILE = 'latest_release';
    const TIME_BETWEEN_CHECKS = '7 days';
    const UPDATE_URL = 'https://api.github.com/repos/pantheon-systems/terminus/releases/latest';

    /**
     * @param DataStoreInterface $data_store
     */
    public function __construct(DataStoreInterface $data_store)
    {
        $this->setDataStore($data_store);
    }

    /**
     * @param string $id Key of the attribute to retrieve
     * @return string
     * @throws TerminusNotFoundException
     */
    public function get($id)
    {
        $attributes = $this->getAttributes();
        if (isset($attributes->$id)) {
            return $attributes->$id;
        }
        throw new TerminusNotFoundException('There is no attribute called {id}.', compact('id'));
    }

    /**
     * Retrieves release data. If it is time to check for an update, it will do that.
     */
    private function fetch()
    {
        $saved_data = (object)$this->getSavedReleaseFromFile();

        if (!isset($saved_data->check_date)
            || (int)$saved_data->check_date < strtotime('-' . self::TIME_BETWEEN_CHECKS)
        ) {
            try {
                $this->attributes = $this->getLatestReleaseFromGithub();
                $this->saveReleaseData($this->attributes);
            } catch (\Exception $e) {
                $this->logger->debug(
                    "Terminus was unable to check the latest release version number.\n{message}",
                    ['message' => $e->getMessage(),]
                );
            }
        }
        if (empty($this->attributes)) {
            $this->attributes = $saved_data;
        }
    }

    /**
     * @return object
     */
    private function getAttributes()
    {
        if (empty($this->attributes)) {
            $this->fetch();
        }
        return $this->attributes;
    }

    /**
     * @return object
     */
    private function getLatestReleaseFromGithub()
    {
        return (object)[
            'version' => $this->request()->request(self::UPDATE_URL)['data']->name,
            'check_date' => time(),
        ];
    }

    /**
     * @return object
     */
    private function getSavedReleaseFromFile()
    {
        return $this->getDataStore()->get(self::SAVE_FILE);
    }

    /**
     * @param object $data
     */
    private function saveReleaseData($data)
    {
        $this->getDataStore()->set(self::SAVE_FILE, $data);
    }
}
