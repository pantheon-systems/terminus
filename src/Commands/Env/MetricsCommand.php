<?php

namespace Pantheon\Terminus\Commands\Env;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Consolidation\OutputFormatters\StructuredData\RowsOfFieldsWithMetadata;

/**
 * Class MetricsCommand
 * @package Pantheon\Terminus\Commands\Env
 */
class MetricsCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    const DAILY_GRANULARITY = 'day';
    const MONTHLY_GRANULARITY = 'month';

    const DEFAULT_MONTHLY_DATAPOINTS = 12;
    const DEFAULT_DAILY_DATAPOINTS = 28;

    /**
     * Displays the metrics (page views, unique visits, etc.) for the
     * environment. The most recent data up to the current day is returned.
     *
     * @authorize
     *
     * @command alpha:env:metrics
     *
     * @field-labels
     *     datetime: Timestamp
     *     value: Value
     * @return RowsOfFieldsWithMetadata
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option series The data series to display (pageviews or uniques)
     * @option granularity The time period for each data point (month or day)
     * @option datapoints How much data to return in total
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     */
    public function metrics(
        $site_env,
        $options = [
            'series' => 'pageviews',
            'granularity' => self::MONTHLY_GRANULARITY,
            'datapoints' => ''
        ]
    ) {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');
        $data = $env->getMetrics()
            ->setSeriesId($options['series'])
            ->setGranularity($options['granularity'])
            ->setDatapoints($options['datapoints'] ?: $this->defaultDatapoints($options['granularity']))
            ->serialize();
        return (new RowsOfFieldsWithMetadata($data))->setDataKey('timeseries');
    }

    // TODO: validate that:
    //   series is 'pageviews' or 'uniques' (future value for unique monthly visitors)
    //   granularity is 'month' or 'day'
    //   'datapoints' is no greater than 12 (month) or 28 (day)

    /**
     * Default datapoints to 12 / 28 if it is not specified
     */
    public function defaultDatapoints($granularity)
    {
        if ($granularity == 'day') {
            return self::DEFAULT_DAILY_DATAPOINTS;
        }
        return self::DEFAULT_MONTHLY_DATAPOINTS;
    }
}
