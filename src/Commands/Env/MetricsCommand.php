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

    const DAILY_PERIOD = 'day';
    const WEEKLY_PERIOD = 'week';
    const MONTHLY_PERIOD = 'month';

    const PAGEVIEW_SERIES = 'pageviews';
    const UNIQUE_VISITS_SERIES = 'visits';

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
     * @aliases alpha:metrics
     *
     * @field-labels
     *     datetime: Timestamp
     *     value: Value
     * @return RowsOfFieldsWithMetadata
     *
     * @param string $site_env Site & environment in the format `site-name.env`
     * @option series The data series to display (pageviews or visits)
     * @option period The time period for each data point (month or day)
     * @option datapoints How much data to return in total, or 'auto' to select
     *   a resonable default based on the selected period.
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     */
    public function metrics(
        $site_env,
        $options = [
            'series' => 'pageviews',
            'period' => self::DAILY_PERIOD,
            'datapoints' => 'auto'
        ]
    ) {
        list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'live');

        if ($env->getName() != 'live') {
            throw new \Exception('Metrics are only supported for the "live" environment for now.');
        }

        $data = $env->getMetrics()
            ->setSeriesId($options['series'])
            ->setPeriod($options['period'])
            ->setDatapoints($this->selectDatapoints($options['datapoints'], $options['period']))
            ->serialize();

        return (new RowsOfFieldsWithMetadata($data))
            ->setDataKey('timeseries')
            ->addRenderer(
               new NumericCellRenderer($data['timeseries'], ['value' => 0])
            );
    }

    protected function findWidth($data, $column)
    {
        $maxWidth = 0;
        foreach ($data as $row) {
            $str = number_format($row[$column]);
            $maxWidth = max($maxWidth, strlen($str));
        }
        return $maxWidth;
    }


    /**
     * Determine the value we should use for 'datapoints'.
     */
    protected function selectDatapoints($datapoints, $period)
    {
        if (!$datapoints || ($datapoints == 'auto')) {
            return $this->defaultDatapoints($period);
        }
        return $datapoints;
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
            self::UNIQUE_VISITS_SERIES,
        ];

        $input = $commandData->input();
        $this->validateOptionValue($input, 'series', $validSeries);
        $this->validateOptionValue($input, 'period', $validGranularities);
        $this->validateItemWithinRange($input, 'datapoints', 1, $this->datapointsMaximum($input->getOption('period')), ['auto']);
    }

    /**
     * Test to see if an option value is one of the provided values
     * @param InputInterface $input
     * @param string $optionName
     * @param string[] $validValues
     */
    protected function validateOptionValue(InputInterface $input, $optionName, array $validValues)
    {
        $value = $input->getOption($optionName);
        if (!in_array($value, $validValues)) {
            throw new \Exception("'{$value}' is an invalid value for {$optionName}: must be one of " . implode(', ', $validValues));
        }
    }

    protected function validateItemWithinRange(InputInterface $input, $optionName, $minimum, $maximum, $exceptionalValues = [])
    {
        $value = $input->getOption($optionName);
        if (in_array($value, $exceptionalValues)) {
            return;
        }
        $orOneOf = (count($exceptionalValues) == 0) ? '' : (count($exceptionalValues) == 1) ? 'or ' : 'or one of ';
        if (($value < $minimum) || ($value > $maximum)) {
            throw new \Exception("'{$value}' is an invalid value for {$optionName}: must be between {$minimum} and {$maximum} (inclusive) {$orOneOf}" . implode(', ', $exceptionalValues));
        }
    }

    /**
     * Default datapoints to 12 / 28 if 'auto' is specified
     */
    public function defaultDatapoints($period)
    {
        // For now, out default values will just be the maximums for the period.
        // We should change this if we increase the maximums.
        return $this->datapointsMaximum($period);
    }

    /**
     * Default datapoints to 12 / 28 if it is not specified
     */
    public function datapointsMaximum($period)
    {
        $defaultPeriodValues = [
            self::DAILY_PERIOD => self::DEFAULT_DAILY_DATAPOINTS,
            self::WEEKLY_PERIOD => self::DEFAULT_WEEKLY_DATAPOINTS,
            self::MONTHLY_PERIOD => self::DEFAULT_MONTHLY_DATAPOINTS,
        ];

        return $defaultPeriodValues[$period];
    }
}
