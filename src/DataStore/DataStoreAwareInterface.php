<?php

namespace Pantheon\Terminus\DataStore;

/**
 * Interface DataStoreAwareInterface
 * @package Pantheon\Terminus\DataStore
 */
interface DataStoreAwareInterface
{
    /***
     * Inject a data store object.
     *
     * @param DataStoreInterface $data_store
     */
    public function setDataStore(DataStoreInterface $data_store);

    /**
     * Get the data store object.
     *
     * @return DataStoreInterface
     */
    public function getDataStore();
}
