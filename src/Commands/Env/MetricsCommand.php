<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\StructuredData\NumericCellRenderer;
use Consolidation\OutputFormatters\StructuredData\RowsOfFieldsWithMetadata;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Class MetricsCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class MetricsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    const DEFAULT_DURATION = '28d';

    /**
     * Displays the pages served and unique visit metrics for the specified
     * site's environment. The most recent data up to the current day is returned.
     *
     * @authorize
     *
     * @command alpha:env:metrics
     * @aliases alpha:metrics
     *
     * @field-labels
     *     datetime: Period
     *     visits: Visits
     *     pages_served: Pages Served
     *     cache_hits: Cache hits
     *     cache_misses: Cache misses
     * @return RowsOfFieldsWithMetadata
     *
     * @param string $site_env Site & environment in the format `site-name.env`.
     *   Defaults to the live environment if `.env` is not specified.
     * @option duration How far back do you wish to look for data. The default is the last 28 days (28d).
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     * @usage <site> Displays the combined metrics for all of <site>'s environments.
     * @usage <site> --fields=datetime,pages_served,cache_hits,cache_misses Displays only the pages
     *   served for each date period.
     */
    public function metrics(
        $site_env,
        $options = [
            'duration' => self::DEFAULT_DURATION
        ]
    ) {
        list($site_id, $env_id) = array_pad(explode('.', $site_env), 2, null);

        if (empty($env_id)) {
            $site = $this->getSite($site_id);

            $data = $site->getSiteMetrics()
                ->setDuration($options['duration'])
                ->serialize();
        } else {
            list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'live');

            $data = $env->getEnvironmentMetrics()
                ->setDuration($options['duration'])
                ->serialize();
        }

        return (new RowsOfFieldsWithMetadata($data))
            ->setDataKey('timeseries')
            ->addRenderer(
                new NumericCellRenderer($data['timeseries'], [
                    'visits' => 6, 
                    'pages_served' => 12,
                    'cache_hits' => 10,
                    'cache_misses' => 12,
                ])
            )
            ->addRendererFunction(
                function ($key, $cellData, FormatterOptions $options, $rowData) {
                    if ($key == 'datetime') {
                        $cellData = str_replace('T00:00:00', '', $cellData);
                    }
                    return $cellData;
                }
            );
    }

    /**
     * Find the maximum width of any data item in the specified column.
     * @param array $data
     * @param string $column
     * @return int
     */
    protected function findWidth($data, $column)
    {
        $maxWidth = 0;
        foreach ($data as $row) {
            $str = number_format($row[$column]);
            $maxWidth = max($maxWidth, strlen($str));
        }
        return $maxWidth;
    }
}
