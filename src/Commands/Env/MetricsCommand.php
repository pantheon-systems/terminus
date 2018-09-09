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

    const DEFAULT_MONTHLY_DATAPOINTS = 12;
    const DEFAULT_WEEKLY_DATAPOINTS = 12;
    const DEFAULT_DAILY_DATAPOINTS = 28;

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
     * @return RowsOfFieldsWithMetadata
     *
     * @param string $site_env Site & environment in the format `site-name.env`.
     *   Defaults to the live environment if `.env` is not specified.
     * @option period The time period for each data point (month|week|day)
     * @option datapoints How much data to return in total, or 'auto' to select
     *   a resonable default based on the selected period.
     *
     * @usage <site>.<env> Displays metrics for <site>'s <env> environment.
     * @usage <site> Displays the combined metrics for all of <site>'s environments.
     * @usage <site> --fields=datetime,pages_served Displays only the pages
     *   served for each date period.
     */
    public function metrics(
        $site_env,
        $options = [
            'period' => self::DAILY_PERIOD,
            'datapoints' => 'auto'
        ]
    ) {
        list($site_id, $env_id) = array_pad(explode('.', $site_env), 2, null);

        if (empty($env_id)) {
            $site = $this->getSite($site_id);

            $data = $site->getSiteMetrics()
                ->setPeriod($options['period'])
                ->setDatapoints($this->selectDatapoints($options['datapoints'], $options['period']))
                ->serialize();
        } else {
            list(, $env) = $this->getUnfrozenSiteEnv($site_env, 'live');

            $data = $env->getEnvironmentMetrics()
                ->setPeriod($options['period'])
                ->setDatapoints($this->selectDatapoints($options['datapoints'], $options['period']))
                ->serialize();
        }

        return (new RowsOfFieldsWithMetadata($data))
            ->setDataKey('timeseries')
            ->addRenderer(
                new NumericCellRenderer($data['timeseries'], ['visits' => 6, 'pages_served' => 12])
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

    /**
     * Determine the value we should use for 'datapoints' given a specific period.
     * @param string $datapoints
     * @param string $period
     * @return string
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
     * @param CommandData $commandData
     */
    public function validateOptions(CommandData $commandData)
    {
        $validGranularities = [
            self::DAILY_PERIOD,
            self::WEEKLY_PERIOD,
            self::MONTHLY_PERIOD,
        ];

        $input = $commandData->input();
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

    /**
     * Check to see if the specified item is within the specified minimum/maximum range.
     * @param InputInterface $input
     * @param string $optionName
     * @param string $minimum
     * @param string $maximum
     * @param string $exceptionalValues
     */
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
     * @param string $period
     * @return string
     */
    public function defaultDatapoints($period)
    {
        // For now, out default values will just be the maximums for the period.
        // We should change this if we increase the maximums.
        return $this->datapointsMaximum($period);
    }

    /**
     * Return the maximum datapoint value for the provided period.
     * @param string $period
     * @return string
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
