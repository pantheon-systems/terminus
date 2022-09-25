<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Collections\APICollection;

/**
 * Class EnvironmentCacheMetrics
 * @package Pantheon\Terminus\Collections
 */
class EnvironmentCacheMetrics extends APICollection
{
    const URL_FORMAT = 'sites/{siteId}/environments/{environmentId}/traffic';

    public function getURLForCacheMetrics($siteID, $environmentID)
    {
        $r = [
            '{siteId}' => $siteID,
            '{environmentId}' => $environmentID,
        ];

        return strtr(self::URL_FORMAT, $r);
    }

    public function getTrafficData($siteID, $environmentID, $duration)
    {
        $args = [
            'options' => [
                'method' => 'get'
            ],
            'query' => [
                'duration' => $duration,
            ],
        ];
        $url = $this->getURLForCacheMetrics($siteID, $environmentID);
        $response = $this->request()->request($url, $args);

        $rows = [];
        foreach ($response['data']->timeseries as $dataRow) {
            $row = (array) $dataRow;
            $row['cache_hit_ratio'] = '--'; // Default value for use if pages_served = 0.

            if ($dataRow->pages_served) {
                $row['cache_hit_ratio'] = number_format($dataRow->cache_hits / $dataRow->pages_served * 100, 2) . '%';
            }

            $rows[] = $row;
        }

        return ['timeseries' => $rows];
    }
}
