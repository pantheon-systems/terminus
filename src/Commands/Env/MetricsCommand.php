<?php

namespace Pantheon\Terminus\Commands\Env;

use Consolidation\AnnotatedCommand\CommandData;
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

    const DAILY_PERIOD = 'day';
    const WEEKLY_PERIOD = 'week';
    const MONTHLY_PERIOD = 'month';

    const PAGEVIEW_SERIES = 'pageviews';
    const UNIQUES_SERIES = 'uniques';

    const DEFAULT_MONTHLY_DATAPOINTS = 12;
    const DEFAULT_WEEKLY_DATAPOINTS = 12;
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
     * @option period The time period for each data point (month or day)
     * @option datapoints How much data to return in total
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     */
    public function metrics(
        $site_env,
        $options = [
            'series' => 'pageviews',
            'period' => self::MONTHLY_PERIOD,
            'datapoints' => ''
        ]
    ) {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'dev');
        $data = $env->getMetrics()
            ->setSeriesId($options['series'])
            ->setPeriod($options['period'])
            ->setDatapoints($options['datapoints'] ?: $this->defaultDatapoints($options['period']))
            ->serialize();
        return (new RowsOfFieldsWithMetadata($data))->setDataKey('timeseries');
    }

    /**
     * Ensure that the user did not supply an invalid value for 'period'.
     *
     * @hook validate alpha:env:metrics
     */
    public function validateOptions(CommandData $commandData)
    {
        $validGranularities = [
            self::DAILY_PERIOD,
            self::WEEKLY_PERIOD,
            self::MONTHLY_PERIOD,
        ];
        $validSeries = [
            self::PAGEVIEW_SERIES,
            self::UNIQUES_SERIES,
        ];

        $input = $commandData->input();
        $this->validateOptionValue($input, 'series', $validSeries);
        $this->validateOptionValue($input, 'period', $validGranularities);
    }

    /**
     * Test to see if an option value is one
     * @param InputInterface $input
     * @param string $optionName
     * @param string[] $validValues
     */
    protected function validateOptionValue(InputInterface $input, $optionName, array $validValues)
    {
        $value = $input->getOption($optionName);
        if (!in_array($value, $validValues)) {
            throw new \Exception("Invalid value for {$optionName}: must be one of " . implode(', ', $validValues));
        }
    }

    /**
     * Default datapoints to 12 / 28 if it is not specified
     */
    public function defaultDatapoints($period)
    {
        $defaultPeriodValues = [
            self::DAILY_PERIOD => self::DEFAULT_DAILY_DATAPOINTS,
            self::WEEKLY_PERIOD => self::DEFAULT_WEEKLY_DATAPOINTS,
            self::MONTHLY_PERIOD => self::DEFAULT_MONTHLY_DATAPOINTS,
        ];

        return $defaultPeriodValues[$period];
    }
}
