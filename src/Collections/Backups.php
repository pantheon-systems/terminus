<?php

namespace Pantheon\Terminus\Collections;

use Terminus\Exceptions\TerminusNotFoundException;

class Backups extends TerminusCollection
{
    const DAILY_BACKUP_TTL = 691200;
    const WEEKLY_BACKUP_TTL = 2764800;

    /**
     * @var Environment
     */
    public $environment;
    /**
     * @var string
     */
    protected $collected_class = 'Pantheon\Terminus\Models\Backup';

    /**
     * @inheritdoc
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
    public function create(array $arg_options = [])
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
        $params['ttl'] = ceil((integer)$options['keep-for'] * 86400);

        return $this->environment->getWorkflows()->create('do_export', compact('params'));
    }

    /**
     * Fetches model data from API and instantiates its model instances
     *
     * @param array $options params to pass configure fetching
     *        array $data Data to fill in the model members of this collection
     * @return TerminusCollection $this
     */
    public function fetch(array $options = [])
    {
        $data = isset($options['data']) ? $options['data'] : $this->getCollectionData($options);

        foreach ($data as $id => $model_data) {
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
            return $this->get(array_shift($matches));
        } catch (\Exception $e) {
            throw new TerminusNotFoundException('Cannot find a backup named {filename}.', compact('filename'));
        }
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
    public function getFinishedBackups($element = null)
    {
        if (!is_null($element)) {
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
     *    string  day  A day of the week
     *    integer hour Hour of the day to run the backups at, 0 = 00:00 23 = 23:00
     * @return Workflow
     */
    public function setBackupSchedule(array $options = ['day' => null, 'hour' => null,])
    {
        $backup_hour = (isset($options['hour']) && !is_null($options['hour'])) ? $options['hour'] : null;
        $day_number = isset($options['day']) ? $this->getDayNumber($options['day']) : rand(0, 6);
        $schedule = [];
        for ($day = 0; $day < 7; $day++) {
            $schedule[$day] = (object)['hour' => $backup_hour, 'ttl' => null,];
            $schedule[$day]->ttl = ($day == $day_number) ? self::WEEKLY_BACKUP_TTL: self::DAILY_BACKUP_TTL;
        }
        $schedule = (object)$schedule;

        $workflow = $this->environment->getWorkflows()->create(
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
        $days_of_the_week = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday',];
        return array_search(date('l', strtotime($day)), $days_of_the_week);
    }
}
