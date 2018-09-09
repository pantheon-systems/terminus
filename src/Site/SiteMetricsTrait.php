<?php

namespace Pantheon\Terminus\Site;

use Pantheon\Terminus\Models\Metric;

/**
 * SiteMetricsTrait
 * @package Pantheon\Terminus\Site
 */
trait SiteMetricsTrait
{
    public static $pretty_name = 'metrics';

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
     * Metrics constructor
     */
    public function __constructor()
    {
    }

    /**
     * @return string
     */
    public function getPeriod()
    {
        return $this->period;
    }

    /**
     * @param string $value
     */
    public function setPeriod($value)
    {
        return $this->setParameter('period', $value);
    }

    /**
     * @return string
     */
    public function getDatapoints()
    {
        return $this->datapoints;
    }

    /**
     * @param string $value
     */
    public function setDatapoints($value)
    {
        return $this->setParameter('datapoints', $value);
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
        $rawPagesServed = $this->requestDataAtUrl($this->getUrlForSeries('pageviews'), $this->getFetchArgs());

        if (empty($rawPagesServed->timeseries)) {
            throw new \Exception("No data available.");
        }

        $rawVisits = $this->requestDataAtUrl($this->getUrlForSeries('visits'), $this->getFetchArgs());

        // The data is passed to us with the data series of primary
        // interest to us nested inside a 'timeseries' element. The
        // requirements for an EnvironmentOwnedCollection or any
        // TerminusCollection is that the request data must return
        // a list of all of our data items.
        // (@see TerminusCollection::fetch())
        // We also need to ensure that the elements of our timeseries
        // have unique IDs. We will use the time value for this.
        $pageviewData = $this->assignIds($rawPagesServed->timeseries, 'timestamp');
        $visitData = $this->assignIds($rawVisits->timeseries, 'timestamp');
        $combineddata = $this->combineRawData($pageviewData, $visitData);

        // Convert the timestamp to a datetime. The timestamp will remain
        // as the row key.
        $data = array_map(
            function ($item) {
                $item->datetime = gmdate("Y-m-d\TH:i:s", $item->timestamp);
                unset($item->timestamp);
                return $item;
            },
            $combineddata
        );

        // Our parent class is already caching our data series; we will
        // store the other items in a 'metadata' field. We will avoid
        // caching the raw data because that would duplicate data
        // already cached.
        unset($rawPagesServed->timeseries);
        $this->metadata = $rawPagesServed;

        return $data;
    }

    /**
     * Combine the raw pages served data with the raw unique visits data
     * @param array $rawPagesServed
     * @param array $rawVisits
     * @return array
     */
    protected function combineRawData($rawPagesServed, $rawVisits)
    {
        $result = $rawVisits;
        foreach ($result as $time => $item) {
            $item->visits = $item->value;
            $result[$time]->pages_served = $rawPagesServed[$time]->value;
            unset($result[$time]->value);
        }
        return $result;
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
     * @return associative array of the same input items with new keys
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
     * @param string $seriesId
     * @return string
     */
    protected function getUrlForSeries($seriesId)
    {
        $url = $this->getUrl();
        $tr = [
            '{series}' => $seriesId,
            '{period}' => $this->getPeriod(),
            '{datapoints}' => $this->getDatapoints(),
        ];
        return strtr($url, $tr);
    }
}
