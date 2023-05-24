<?php

namespace Pantheon\Terminus\DataStore;

/**
 * Class DataStoreAwareTrait
 * @package Pantheon\Terminus\DataStore
 */
trait DataStoreAwareTrait
{
    /**
     * @var DataStoreInterface
     */
    protected DataStoreInterface $data_store;

    /**
     * @return DataStoreInterface
     */
    public function getDataStore(): DataStoreInterface
    {
        return $this->data_store;
    }

    /**
     * @param DataStoreInterface $data_store
     */
    public function setDataStore(DataStoreInterface $data_store)
    {
        $this->data_store = $data_store;
    }
}
