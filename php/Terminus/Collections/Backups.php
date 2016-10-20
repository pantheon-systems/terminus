<?php

namespace Terminus\Collections;

use Terminus\Exceptions\TerminusNotFoundException;

define('DAILY_BACKUP_TTL', 691200);
define('WEEKLY_BACKUP_TTL', 2764800);

class Backups extends TerminusCollection
{
  /**
   * Valid backup types
   *
   * @return String[] An array of valid elements
   */
    public static function getValidElements()
    {
        return ['code', 'files', 'database', 'db'];
    }

  /**
   * @var Environment
   */
    public $environment;
  /**
   * @var string
   */
    protected $collected_class = 'Terminus\Models\Backup';

  /**
   * Object constructor
   *
   * @param array $options Options to set as $this->key
   */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->environment = $options['environment'];
        $this->url = sprintf(
            'sites/%s/environments/%s/backups/catalog',
            $this->environment->site->id,
            $this->environment->id
        );
    }

  /**
   * Cancels an environment's regular backup schedule
   *
   * @return bool True if operation was successful
   */
    public function cancelBackupSchedule()
    {
        $path_root = sprintf(
            'sites/%s/environments/%s/backups/schedule',
            $this->environment->site->id,
            $this->environment->id
        );
        $params = ['method' => 'delete',];
        for ($day = 0; $day < 7; $day++) {
            $this->request->request("$path_root/$day", $params);
        }
        return true;
    }

  /**
   * Creates a backup
   *
   * @param array $arg_params Array of args to dictate backup choices,
   *   which may have the following keys:
   *   - type: string: Sort of operation to conduct (e.g. backup)
   *   - keep-for: int: Days to keep the backup for
   *   - element: string: Which aspect of the arg to back up
   * @return Workflow
   */
    public function create(array $arg_params = [])
    {
        $default_params = [
        'code'       => false,
        'database'   => false,
        'files'      => false,
        'ttl'        => 31556736,
        'entry_type' => 'backup',
        ];
        $params = array_merge($default_params, $arg_params);

        if (isset($params[$params['element']])) {
            $params[$params['element']] = true;
        } else {
            $params = array_merge(
                $params,
                ['code' => true, 'database' => true, 'files' => true,]
            );
        }
        unset($params['element']);

        if (isset($params['keep-for'])) {
            $params['ttl'] = ceil((integer)$params['keep-for'] * 86400);
            unset($params['keep-for']);
        }

        if (isset($params['type'])) {
            $params['entry_type'] = $params['type'];
            unset($params['type']);
        }

        $workflow = $this->environment->workflows->create(
            'do_export',
            compact('params')
        );
        return $workflow;
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
        $matches = $this->getFilteredMemberList(compact('filename'), 'id', 'id');
        try {
            $backup = $this->get(array_shift($matches));
        } catch (\Exception $e) {
            throw new TerminusNotFoundException(
                'Cannot find a backup named {filename}.',
                compact('filename'),
                1
            );
        }
        return $backup;
    }

  /**
   * Lists all backups for a specific element.
   *
   * @param string $element Name of the element type to filter by
   * @return Backup[]
   */
    public function getBackupsByElement($element = null)
    {
        $backups = array_filter(
            $this->all(),
            function ($backup) use ($element) {
                return $backup->getElement() == $element;
            }
        );

        return $backups;
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
        $path     = sprintf(
            'sites/%s/environments/%s/backups/schedule',
            $this->environment->site->id,
            $this->environment->id
        );
        $response      = $this->request->request($path);
        $response_data = (array)$response['data'];
        $data          = [
        'daily_backup_hour' => null,
        'weekly_backup_day' => null,
        ];

        $schedule_sample = array_shift($response_data);
        if (!is_null($schedule_sample)) {
            $schedule = [];
            foreach ((array)$response['data'] as $day_number => $info) {
                $schedule[$day_number] = $info->ttl;
            }
            $day_number      = array_search(max($schedule), $schedule);
            $data['weekly_backup_day'] = date(
                'l',
                strtotime("Sunday +{$day_number} days")
            );
            $data['daily_backup_hour'] = date('H T', strtotime($info->hour . ':00'));
        }
        return $data;
    }

  /**
   * Filters the backups for only ones which have finished
   *
   * @param string $element Element requested (i.e. code, db, or files)
   * @return Backup[] An array of Backup objects
   */
    public function getFinishedBackups($element)
    {
        if ($element != null) {
            $all_backups = $this->getBackupsByElement($element);
        } else {
            $all_backups = $this->all();
        }

        $finished_backups = array_filter(
            $all_backups,
            function ($backup) {
                return $backup->backupIsFinished();
            }
        );
        $ordered_backups  = [];
        foreach ($finished_backups as $id => $backup) {
            $ordered_backups[$id] = $backup->get('start_time');
        }
        arsort($ordered_backups);
        $backups = [];
        foreach ($ordered_backups as $id => $start_time) {
            $backups[] = $finished_backups[$id];
        }

        return $backups;
    }

  /**
   * Sets an environment's regular backup schedule
   *
   * @param array $options Elements as follow:
   *    string  day  A day of the week
   *    integer hour Hour of the day to run the backups at, 1 = 01:00 24 = 00:00
   * @return Workflow
   */
    public function setBackupSchedule($options)
    {
        $daily_ttl = 691200;
        $weekly_ttl = 2764800;
        $backup_hour = (isset($options['hour']) && !is_null($options['hour'])) ? $options['hour'] : rand(1, 24);
        $day_number = (isset($options['day']) && $options['day']) ? $this->getDayNumber($options['day']) : rand(0, 6);
        $schedule = [];
        for ($day = 0; $day < 7; $day++) {
            $schedule[$day] = (object)['hour' => $backup_hour, 'ttl' => null,];
            $schedule[$day]->ttl = ($day == $day_number) ? $weekly_ttl : $daily_ttl;
        }
        $schedule = (object)$schedule;

        $workflow = $this->environment->workflows->create(
            'change_backup_schedule',
            ['params' => ['backup_schedule' => $schedule,],]
        );
        return $workflow;
    }

    /**
     * Retrieve an integer representing a the day of the week
     *
     * @param string $day The day of the week
     * @return integer 0 = Sunday, 6 = Saturday
     */
    protected function getDayNumber($day)
    {
        $days = [
            'Sunday',
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
        ];
        return array_search(date('l', strtotime($day)), $days);
    }
}
