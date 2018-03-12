<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Models\Metric;

/**
 * Class Metrics
 * @package Pantheon\Terminus\Collections
 */
class Metrics extends EnvironmentOwnedCollection
{
    protected $metadata;
    protected $seriesId;
    protected $granularity;
    protected $datapoints;

    public static $pretty_name = 'metrics';
    /**
     * @var string
     */
    protected $collected_class = Metric::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/{series}?granularity={granularity}&datapoints={datapoints}';

    /**
     * Supported series:
     *
     * - pageviews
     * - uniques
     */
    public function __constructor($seriesId)
    {
        $this->seriesId = $seriesId;
    }

    public function getSeriesId()
    {
        return $this->seriesId;
    }

    public function setSeriesId($value)
    {
        return $this->setParameter('seriesId', $value);
    }

    public function getGranularity()
    {
        return $this->granularity;
    }

    public function setGranularity($value)
    {
        return $this->setParameter('granularity', $value);
    }

    public function getDatapoints()
    {
        return $this->datapoints;
    }

    public function setDatapoints($value)
    {
        return $this->setParameter('datapoints', $value);
    }

    protected function setParameter($parameter, $value)
    {
        if ($this->$parameter === $value) {
            return $this;
        }
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
        $rawdata = parent::requestData();

        if (empty($rawdata->timeseries)) {
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
        $data = $this->assignIds($rawdata->timeseries, 'timestamp');

        // Convert the timestamp to a datetime. The timestamp will remain
        // as the row key.
        $data = array_map(
            function ($item) {
                $item->datetime = gmdate("Y-m-d\TH:i:s", $item->timestamp);
                unset($item->timestamp);
                return $item;
            },
            $data
        );

        // Our parent class is already caching our data series; we will
        // store the other items in a 'metadata' field. We will avoid
        // caching the raw data because that would duplicate data
        // already cached.
        unset($rawdata->timeseries);
        $this->metadata = $rawdata;

        return $data;
    }

    /**
     * When serializing the metrics again, wrap the timeseries data
     * back inside a 'timeseries' element and then union in the metadata.
     */
    public function serialize()
    {
        $timeseries = parent::serialize();
        return (array) $this->metadata + ['timeseries' => $timeseries];
    }

    /**
     * Fill in the selected values for the data series, granularity
     * and datapoints.
     */
    protected function replaceUrlTokens($url)
    {
        $url = parent::replaceUrlTokens($url);
        $tr = [
            '{series}' => $this->getSeriesId(),
            '{granularity}' => $this->getGranularity(),
            '{datapoints}' => $this->getDatapoints(),
        ];
        return strtr($url, $tr);
    }

    /**
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
}
