<?php

namespace Pantheon\Terminus\Collections;

use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\Models\BackupSet;
use Pantheon\Terminus\Models\Backup;
use Pantheon\Terminus\Models\Workflow;

/**
 * Class Backups
 * @package Pantheon\Terminus\Collections
 */
class Backups extends EnvironmentOwnedCollection
{
    const DAILY_BACKUP_TTL = 8;
    const SECONDS_IN_A_DAY = 86400;
    const WEEKLY_BACKUP_TTL = 32;

    const PRETTY_NAME = 'backups';
    /**
     * @var string
     */
    protected $collected_class = Backup::class;
    /**
     * @var string
     */
    protected $url = 'sites/{site_id}/environments/{environment_id}/backups/catalog';

    /**
     * Cancels an environment's regular backup schedule
     */
    public function cancelBackupSchedule()
    {
        $path_root = $this->replaceUrlTokens('sites/{site_id}/environments/{environment_id}/backups/schedule');
        $params = ['method' => 'delete',];
        for ($day = 0; $day < 7; $day++) {
            $this->request()->request("$path_root/$day", $params);
        }
    }

    /**
     * Creates a backup
     *
     * @param array $arg_options Elements as follow:
     *   integer keep-for Days to keep the backup for
     *   string  element  Which element of the site to back up (database, code, files, or null for all)
     * @return Workflow
     */
    public function create(array $arg_options = []): Workflow
    {
        $default_options = ['element' => null, 'keep-for' => 365,];
        $options = array_merge($default_options, $arg_options);

        $params = [
            'code'       => false,
            'database'   => false,
            'files'      => false,
            'entry_type' => 'backup',
        ];

        if (!is_null($element = $options['element'])) {
            $params[$element] = true;
        } else {
            $params['code'] = $params['database'] = $params['files'] = true;
        }
        $params['ttl'] = self::convertDaysToSeconds($options['keep-for']);

        return $this->getEnvironment()->getWorkflows()->create('do_export', compact('params'));
    }

    /**
     * Fetches model data from API and instantiates its model instances only if a filename is present in the model data
     *
     * @return Backups $this
     */
    public function fetch(): Backups
    {
        foreach ($this->getData() as $id => $model_data) {
            if (isset($model_data->filename)) {
                if (!isset($model_data->id)) {
                    $model_data->id = $id;
                }
                $this->add($model_data);
            }
        }
        return $this;
    }

    /**
     * Filters out backups which are not of the given type
     *
     * @param string [code|files|database] The element desired of the backup collection
     * @return Backups
     */
    public function filterForElement($element)
    {
        return $this->filter(function ($backup) use ($element) {
            return $backup->get('type') === $element;
        });
    }

    /**
     * Filters out unfinished backups
     *
     * @return Backups
     */
    public function filterForFinished()
    {
        return $this->filter(function ($backup) {
            return $backup->backupIsFinished();
        });
    }

    /**
     * Fetches backup for a specified filename
     *
     * @param string $filename Name of the file name to filter by
     * @return Backup
     * @throws TerminusNotFoundException
     */
    public function getBackupByFileName($filename)
    {
        return $this->get($filename);
    }

    /**
     * Lists all backups for a specific element.
     *
     * @param string $element Name of the element type to filter by
     * @return Backup[]
     */
    public function getBackupsByElement($element = null)
    {
        return array_filter(
            $this->all(),
            function ($backup) use ($element) {
                return $backup->get('type') == $element;
            }
        );
    }

    /**
     * Retrieves an environment's regular backup schedule
     *
     * @return array $schedule Elements as follows:
     *   - daily_backup_time: string
     *   - weekly_backup_day: string
     */
    public function getBackupSchedule()
    {
        $path = $this->replaceUrlTokens('sites/{site_id}/environments/{environment_id}/backups/schedule');

        $response      = $this->request->request($path);
        $response_data = (array)$response['data'];
        $data          = [
            'daily_backup_hour' => null,
            'expiry' => null,
            'weekly_backup_day' => null,
        ];

        $schedule_sample = array_shift($response_data);
        if (!is_null($schedule_sample)) {
            $schedule = [];
            foreach ((array)$response['data'] as $day_number => $info) {
                $schedule[$day_number] = $info->ttl;
            }
            $weekly_ttl = max($schedule);
            $day_number = array_search($weekly_ttl, $schedule);
            $data['weekly_backup_day'] = date(
                'l',
                strtotime("Sunday +{$day_number} days")
            );
            $data['daily_backup_hour'] = date('H T', strtotime($info->hour . ':00'));
            $weekly_ttl_days = self::convertSecondsToDays($weekly_ttl);
            $data['expiry'] = $weekly_ttl_days . ' day';
            if ($weekly_ttl_days > 1) {
                $data['expiry'] .= 's';
            }
        }
        return $data;
    }

    /**
     * Filters the backups for only ones which have finished
     *
     * @param string $element Element requested (i.e. code, db, or files)
     * @return BackupSet[] An array of Backup objects
     */
    public function getFinishedBackups($element = null)
    {
        $all_backups = !is_null($element) ? $this->getBackupsByElement($element) : $this->all();
        $finished_backups = array_filter($all_backups, function ($backup) {
            return $backup->backupIsFinished();
        });
        $backup_ids = array_keys($finished_backups);
        rsort($backup_ids);
        $backups = [];
        foreach ($backup_ids as $id) {
            $scheduled_for = $finished_backups[$id]->get('scheduled_for');
            if (!isset($backups[$scheduled_for])) {
                $backups[$scheduled_for] = [];
            }
            $backups[$scheduled_for][$id] = $finished_backups[$id];
        }

        $backup_sets = [];
        
        foreach ($backups as $timestamp => $items) {
            $backup_sets[] = new BackupSet((object)[
                'timestamp' => $timestamp,
                'items' => $items,
            ]);
        }
        return $backup_sets;
    }

    /**
     * Valid backup types
     *
     * @return string[] An array of valid elements
     */
    public function getValidElements()
    {
        return ['code', 'files', 'database', 'db',];
    }

    /**
     * Sets an environment's regular backup schedule
     *
     * @param array $options Elements as follow:
     *    integer daily-ttl  Days to keep the daily backups for
     *    string  day        A day of the week
     *    integer hour       Hour of the day to run the backups at, 0 = 00:00 23 = 23:00
     *    integer weekly-ttl Days to keep the weekly backups for
     * @return Workflow
     */
    public function setBackupSchedule(array $options = [
        'daily-ttl' => null,
        'day' => null,
        'hour' => null,
        'weekly-ttl' => null,
    ])
    {
        $option_exists = function ($option_name) use ($options) {
            return isset($options[$option_name]) && !is_null($options[$option_name]);
        };
        $daily_ttl = self::convertDaysToSeconds(
            $option_exists('daily-ttl') ? $options['daily-ttl'] : Backups::DAILY_BACKUP_TTL
        );
        $weekly_ttl = self::convertDaysToSeconds(
            $option_exists('weekly-ttl') ? $options['weekly-ttl'] : Backups::WEEKLY_BACKUP_TTL
        );
        $backup_hour = $option_exists('hour') ? $options['hour'] : null;
        $day_number = (int)$option_exists('day') ? $this->getDayNumber($options['day']) : rand(0, 6);

        $schedule = [];
        for ($day = 0; $day < 7; $day++) {
            $schedule[$day] = (object)[
                'hour' => $backup_hour,
                'ttl' => ($day === $day_number) ? $weekly_ttl : $daily_ttl,
            ];
        }
        $schedule = (object)$schedule;

        $workflow = $this->getEnvironment()->getWorkflows()->create(
            'change_backup_schedule',
            ['params' => ['backup_schedule' => $schedule,],]
        );
        return $workflow;
    }

    /**
     * Converts seconds to days
     *
     * @param integer $number_of_days
     * @return integer The number of seconds in as many days
     */
    public static function convertDaysToSeconds($number_of_days)
    {
        return (integer)ceil((integer)$number_of_days * self::SECONDS_IN_A_DAY);
    }

    /**
     * Converts days to seconds
     *
     * @param integer $number_of_seconds
     * @return integer The number of days in as many seconds
     */
    public static function convertSecondsToDays($number_of_seconds)
    {
        return (integer)ceil((integer)$number_of_seconds / self::SECONDS_IN_A_DAY);
    }

    /**
     * Retrieve an integer representing a the day of the week
     *
     * @param string $day The day of the week
     * @return integer 0 = Sunday, 6 = Saturday
     */
    protected function getDayNumber($day)
    {
        $days_of_the_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',];
        return array_search(date('l', strtotime($day)), $days_of_the_week);
    }
}
