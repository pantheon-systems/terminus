<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;
use Terminus\Models\Workflow;
use Terminus\Models\Backup;

define('DAILY_BACKUP_TTL', 691200);
define('WEEKLY_BACKUP_TTL', 2764800);

class Backups extends TerminusCollection {

  /**
   * Cancels an environment's regular backup schedule
   *
   * @return bool True if operation was successful
   */
  public function cancelBackupSchedule() {
    $path_root = sprintf(
      'sites/%s/environments/%s/backups/schedule',
      $this->environment->site->get('id'),
      $this->environment->get('id')
    );
    $params    = array('method' => 'delete');
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
  public function create(array $arg_params) {
    $default_params = array(
      'code'       => false,
      'database'   => false,
      'files'      => false,
      'ttl'        => 31556736,
      'entry_type' => 'backup',
    );
    $params = array_merge($default_params, $arg_params);

    if (isset($params[$params['element']])) {
      $params[$params['element']] = true;
    } else {
      $params = array_merge(
        $params,
        array('code' => true, 'database' => true, 'files' => true,)
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

    $options  = array(
      'environment' => $this->environment->get('id'),
      'params' => $params
    );
    $workflow = $this->environment->site->workflows->create(
      'do_export',
      $options
    );
    return $workflow;
  }

  /**
   * Fetches backup for a specified filename
   *
   * @param string $filename Name of the file name to filter by
   * @return Backup
   * @throws TerminusException
   */
  public function getBackupByFileName($filename) {
    $matches = $this->getFilteredMemberList(compact('filename'), 'id', 'id');
    try {
      $backup = $this->get(array_shift($matches));
    } catch (\Exception $e) {
      throw new TerminusException(
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
  public function getBackupsByElement($element = null) {
    $backups = array_filter(
      $this->all(),
      function($backup) use ($element) {
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
  public function getBackupSchedule() {
    $path     = sprintf(
      'sites/%s/environments/%s/backups/schedule',
      $this->environment->site->get('id'),
      $this->environment->get('id')
    );
    $response      = $this->request->request($path);
    $response_data = (array)$response['data'];
    $data          = array(
      'daily_backup_hour' => null,
      'weekly_backup_day' => null,
    );

    $schedule_sample = array_shift($response_data);
    if (!is_null($schedule_sample)) {
      $schedule = array();
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
   * @throws TerminusException
   */
  public function getFinishedBackups($element) {
    if ($element != null) {
      $all_backups = $this->getBackupsByElement($element);
    } else {
      $all_backups = $this->all();
    }

    if (empty($all_backups)) {
      $message  = 'No backups available. Please create one with ';
      $message .= '`terminus site backups create --site={site} --env={env}`';
      throw new TerminusException(
        $message,
        [
          'site' => $this->environment->site->get('name'),
          'env'  => $this->environment->get('id')
        ],
        1
      );
    }

    $finished_backups = array_filter(
      $all_backups,
      function($backup) {
        return $backup->backupIsFinished();
      }
    );
    $ordered_backups  = array();
    foreach ($finished_backups as $id => $backup) {
      $ordered_backups[$id] = $backup->get('start_time');
    }
    arsort($ordered_backups);
    $backups = array();
    foreach ($ordered_backups as $id => $start_time) {
      $backups[] = $finished_backups[$id];
    }

    return $backups;
  }

  /**
   * Sets an environment's regular backup schedule
   *
   * @param int $day_number A numerical of a day of the week
   * @return bool True if operation was successful
   */
  public function setBackupSchedule($day_number) {
    $daily_ttl   = 691200;
    $weekly_ttl  = 2764800;
    $backup_hour = rand(1, 24);
    $schedule    = array();
    for ($day = 0; $day < 7; $day++) {
      $schedule[$day] = (object)array(
        'hour' => $backup_hour,
        'ttl'  => $daily_ttl,
      );
      if ($day == $day_number) {
        $schedule[$day]->ttl = $weekly_ttl;
      }
    }
    $schedule = (object)$schedule;

    $path = sprintf(
      'sites/%s/environments/%s/backups/schedule',
      $this->environment->site->get('id'),
      $this->environment->get('id')
    );

    $params = array(
      'method'      => 'put',
      'form_params' => $schedule,
    );

    $this->request->request($path, $params);
    return true;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return string URL to use in fetch query
   */
  protected function getFetchUrl() {
    $url = sprintf(
      'sites/%s/environments/%s/backups/catalog',
      $this->environment->site->get('id'),
      $this->environment->get('id')
    );
    return $url;
  }

  /**
   * Gets the name of the model-owner of this collection
   *
   * @return string
   */
  protected function getOwnerName() {
    $owner_name = 'environment';
    return $owner_name;
  }

}
