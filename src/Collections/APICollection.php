<?php

namespace Pantheon\Terminus\Collections;

/**
 * Class APICollection
 * @package Pantheon\Terminus\Collections
 */
abstract class APICollection extends TerminusCollection
{
    /**
     * @return array
     */
    private $fetch_args = [];
    /**
     * @var boolean
     */
    protected $paged = false;
    /**
     * @var string
     */
    protected $url;

    /**
     *
     * @return array
     */
    public function getData()
    {
        if (empty(parent::getData())) {
            $this->setData(array_filter((array)$this->requestData()));
        }
        return parent::getData();
    }

    /**
     * @return array
     */
    public function getFetchArgs()
    {
        return $this->fetch_args;
    }

    /**
     * @return bool
     */
    public function isPaged()
    {
        return $this->paged;
    }

    /**
     * @param array $fetch_args
     */
    public function setFetchArgs(array $fetch_args)
    {
        $this->fetch_args = $fetch_args;
    }

    /**
     * @param bool $paged
     * @return APICollection $this
     */
    public function setPaging($paged)
    {
        $this->paged = $paged;
        return $this;
    }

    /**
     * Get the listing URL for this collection
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Retrieves the collection data from the API via a cURL request
     *
     * @return array
     */
    protected function requestData()
    {
        return $this->requestDataAtUrl($this->getUrl(), $this->getFetchArgs());
    }

    /**
     * Make a request at a specific URL
     * @param string $url address to fetch
     * @param array $args request arguments (@see APICollection::getFetchArgs())
     * @return array
     */
    protected function requestDataAtUrl($url, $args = [])
    {
        $default_args = ['options' => ['method' => 'get',],];
        $args = array_merge($default_args, $args);

        if ($this->isPaged()) {
            $results = $this->request()->pagedRequest($url, $args);
        } else {
            $results = $this->request()->request($url, $args);
        }

        return $results['data'];
    }
}
