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
     * @var string How far back do you wish to retreive data
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
        $trafficData = $this->requestDataAtUrl($this->getUrlForSeries('traffic'), $this->getFetchArgs());
        // Convert the timestamp to a datetime. The timestamp will remain
        // as the row key.
        $data = array_map(
            function ($item) {
                $item->datetime = gmdate("Y-m-d\TH:i:s", $item->timestamp);
                unset($item->timestamp);
                return $item;
            },
            $trafficData->timeseries
        );
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
        return (array) ['timeseries' => $timeseries];
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
            '{duration}' => $this->getDuration()
        ];
        return strtr($url, $tr);
    }
}
