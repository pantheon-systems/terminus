<?php

namespace Terminus\Models\Collections;

use Terminus\Exceptions\TerminusException;

class Backups extends TerminusCollection {

  /**
   * Creates a backup
   *
   * @param [array] $arg_params Array of args to dictate backup choices
   *   [string]  type     Sort of operation to conduct (e.g. backup)
   *   [integer] keep-for Days to keep the backup for
   *   [string]  element  Which aspect of the arg to back up
   * @return [Workflow] $workflow
   */
  public function create($arg_params) {
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
   * Lists all backups
   *
   * @param [string] $filename Name of the file name to filter by
   * @return [array] $backup
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
   * Lists all backups
   *
   * @param [string] $element Name of the element type to filter by
   * @return [array] $backups
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
   * Filters the backups for only ones which have finished
   *
   * @param [string] $element Element requested (i.e. code, db, or files)
   * @return [array] $backups An array of stdClass objects representing backups
   */
  public function getFinishedBackups($element) {
    if ($element != null) {
      $all_backups = $this->getBackupsByElement($element);
    } else {
      $all_backups = $this->all();
    }

    if (empty($all_backups)) {
      $message  = 'No backups available. Please create one with ';
      $message .= '`terminus site backup create --site={site} --env={env}`';
      throw new TerminusException(
        $message,
        array(
          'site' => $this->environment->site->get('name'),
          'env'  => $this->environment->get('id')
        ),
        1
      );
    }

    $backups = array_filter(
      $all_backups,
      function($backup) {
        return $backup->backupIsFinished();
      }
    );

    return $backups;
  }

  /**
   * Give the URL for collection data fetching
   *
   * @return [string] $url URL to use in fetch query
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
   * Names the model-owner of this collection
   *
   * @return [string] $owner_name
   */
  protected function getOwnerName() {
    $owner_name = 'environment';
    return $owner_name;
  }

}
