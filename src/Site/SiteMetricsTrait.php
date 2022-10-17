<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Models\Metric;

/**
 * SiteMetricsTrait
 * @package Pantheon\Terminus\Site
 */
trait SiteMetricsTrait
{
    /**
     * @var array
     */
    protected $metadata;

    /**
     * @var string The period of data to fix (month/week/day)
     */
    protected $period;

    /**
     * @var string The number of data points to fetch (28/12 max)
     */
    protected $datapoints;

    /**
     * @var string Formatted datapoints and period
     */
    protected $duration;

    /**
     * Metrics constructor
     */
    public function __constructor()
    {
    }

    /**
     * @return string
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param string $value
     */
    public function setDuration($value)
    {
        return $this->setParameter('duration', $value);
    }

    /**
     * @param string $value
     */
    protected function setParameter($parameter, $value)
    {
        if ($this->$parameter === $value) {
            return $this;
        }
        // Clear our cache whenever our parameters are changed
        $this->setData([]);
        $this->$parameter = $value;
        return $this;
    }

    /**
     * Our API returns our data series inside a 'timeseries' element.
     * To be compatible with Terminus' data model, we need to unwrap
     * this and return an array of data items. We also convert the
     * timestamp from seconds to the date format used elsewhere in Terminus.
     */
    protected function requestData()
    {
        $rawMetrics = $this->requestDataAtUrl($this->getDataUrl(), $this->getFetchArgs());

        if (empty($rawMetrics)) {
            throw new \Exception("No data available.");
        }

        // The data is passed to us with the data series of primary
        // interest to us nested inside a 'timeseries' element. The
        // requirements for an EnvironmentOwnedCollection or any
        // TerminusCollection is that the request data must return
        // a list of all of our data items.
        // (@see TerminusCollection::fetch())
        // We also need to ensure that the elements of our timeseries
        // have unique IDs. We will use the time value for this.
        $combineddata = $this->assignIds($rawMetrics->timeseries, 'timestamp');

        // Format for display
        $data = array_map(
            function ($item) {
                $item->datetime = $item->time;
                unset($item->time);
                unset($item->timestamp);
                $item->cache_hit_ratio = $this->getCacheHitRatio($item->pages_served, $item->cache_hits);
                return $item;
            },
            $combineddata
        );

        // Our parent class is already caching our data series; we will
        // store the other items in a 'metadata' field. We will avoid
        // caching the raw data because that would duplicate data
        // already cached.
        unset($rawMetrics->timeseries);
        $this->metadata = $rawMetrics;

        return $data;
    }

    /**
     * Get a percentage cache hit ratio based on cache hits and misses
     * @param int $pages_served
     * @param int $cache_hits
     * @return array
     */
    protected function getCacheHitRatio($pages_served, $cache_hits)
    {
        if (!$pages_served) {
            return '--';
        }

        return number_format($cache_hits / $pages_served * 100, 2) . '%';
    }

    /**
     * When serializing the metrics again, wrap the timeseries data
     * back inside a 'timeseries' element and then union in the metadata.
     * @return array
     */
    public function serialize()
    {
        $timeseries = parent::serialize();
        return (array) $this->metadata + ['timeseries' => $timeseries];
    }

    /**
     * Convert an array with numeric indexes to an associative array whose
     * indexes are taken from one of the data elements.
     * @param $data An array of items with numeric indexes
     * @param $keyId The id of the element in each item that is the key
     *
     * @return array of the same input items with new keys
     */
    protected function assignIds($data, $keyId)
    {
        // Return an array consisting of all of the values of
        // the data column identified by $keyId.
        $keys = array_map(
            function ($item) use ($keyId) {
                return $item->$keyId;
            },
            $data
        );

        return array_combine($keys, $data);
    }

    /**
     * Fill in the parameters for the desired request.
     * @return string
     */
    protected function getDataUrl()
    {
        $url = $this->getUrl();
        $tr = [
            '{duration}' => $this->getDuration(),
        ];
        return strtr($url, $tr);
    }
}
