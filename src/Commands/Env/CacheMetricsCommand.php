<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\StructuredData\NumericCellRenderer;
use Consolidation\OutputFormatters\StructuredData\RowsOfFieldsWithMetadata;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Request\RequestAwareTrait;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Pantheon\Terminus\Collections\EnvironmentCacheMetrics;

/**
 * Class CacheMetricsCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class CacheMetricsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;
    use RequestAwareTrait;

    const DEFAULT_DURATION = '28d';

    private $cacheMetricsCollection;


    /**
     * Displays the cache hits and misses metrics for the specified
     * site's environment.
     *
     * @authorize
     *
     * @command env:cache-metrics
     *
     * @field-labels
     *     time: Period
     *     pages_served: Pages Served
     *     cache_hits: Cache Hits
     *     cache_misses: Cache Misses
     *     cache_hit_ratio: Cache Hit Ratio
     * @param string $siteAndEnvironment Site & environment in the format `site-name.env`.
     *   Defaults to the live environment if `.env` is not specified.
     * @param array $options
     * @return RowsOfFieldsWithMetadata
     *
     * @throws TerminusException
     * @option duration The duration for the data interval (m|w|d)
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     * @usage <site> Displays the combined metrics for all of <site>'s environments.
     */
    public function cacheMetrics($siteAndEnvironment, $options = ['duration' => self::DEFAULT_DURATION])
    {
        $this->validateDuration($options['duration']);
        list($siteID, $environmentID) = array_pad(explode('.', $siteAndEnvironment), 2, "live");
        $site = $this->getSite($siteID);
        $metricsCollection = $this->cacheMetricsCollection();
        // Get data
        $data = $metricsCollection->getTrafficData($site->id, $environmentID, $options['duration']);
        return (new RowsOfFieldsWithMetadata($data))
            ->setDataKey('timeseries')
            ->addRenderer(
                new NumericCellRenderer($data['timeseries'], [
                    'pages_served' => 12,
                    'cache_hits' => 12,
                    'cache_misses' => 12,
                ])
            )
            ->addRendererFunction(
                function ($key, $cellData, FormatterOptions $options, $rowData) {
                    if ($key == 'time') {
                        $cellData = str_replace('T00:00:00Z', '', $cellData);
                    }
                    return $cellData;
                }
            );
    }

    /**
     * @return EnvironmentCacheMetrics
     */
    private function cacheMetricsCollection()
    {
        if (!$this->cacheMetricsCollection) {
            $container = $this->getContainer();
            $nickname = \uniqid(__FUNCTION__ . "-");
            $container->add($nickname, EnvironmentCacheMetrics::class);
            $this->cacheMetricsCollection = $container->get($nickname);
        }

        return $this->cacheMetricsCollection;
    }

    /**
     * Ensure that the user did not supply an invalid value for 'duration'.
     *
     * @param string $duration
     * @throws TerminusException
     */
    protected function validateDuration($duration)
    {
        $timeUnit = substr($duration, -1);
        $items = (int)explode($timeUnit, $duration)[0];
        $valid = in_array($timeUnit, ['d', 'w', 'm']) && $items > 0 && $items <= 30;

        if (!$valid) {
            throw new TerminusException("Invalid duration parameter. "
                . "Acceptable format: 7d, 12w, 6m. Maximum acceptable durations: 28d, 12w, 12m");
        }
    }
}
