<?php
namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Models\Collections\Sites;

/**
 * Finds and problematic blob values from MariaDB.
 *
 * These debugging commands can be invoked with:
 * `terminus blob columns`
 * `terminus blob cells`
 *
 * @command blob
 */
class BlobBlotterCommand extends TerminusCommand {

  /**
   * Object constructor
   *
   * @param array $options Options to construct the command object
   * @return BlobBlotterCommand
   */
  public function __construct(array $options = []) {
    $options['require_login'] = true;
    parent::__construct($options);
    $this->sites = new Sites();
  }

  /**
   * Retrieve connection info for mysql.
   *
   * @param array $assoc_args
   * @return array of mysql_params
   */
  protected function _openConnection($assoc_args) {
    $site = $this->sites->get(
      $this->input()->siteName(array('args' => $assoc_args))
    );

    $env_id = $this->input()->env(
      array('args' => $assoc_args, 'site' => $site)
    );

    $environment = $site->environments->get($env_id);
    $info        = $environment->connectionInfo();

    $connect = mysqli_connect(
      $info['mysql_host'],
      $info['mysql_username'],
      $info['mysql_password'],
      'pantheon',
      $info['mysql_port']
    );

    return $connect;
  }

  /**
   * Closes the mysql connection.
   */
  protected function _closeConnection($connect) {
    mysqli_close($connect);
  }

  /**
   * Returns the mediumblob/mediumtext/longblob/longtext columns from the pantheon database.
   */
  protected function _getBlobColumns($connect) {
    $query = 'SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
      FROM INFORMATION_SCHEMA.COLUMNS
      WHERE TABLE_SCHEMA = "pantheon"
      AND DATA_TYPE in ("mediumblob", "mediumtext", "longblob", "longtext")';

    if ($result = mysqli_query($connect, $query)) {
      $return = [];
      while ($row = $result->fetch_assoc()) {
        $return[] = $row;
      }
      $result->free();
    }

    return $return;
  }

  /**
   * Finds the biggest blob/text columns from the database.
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of the site
   *
   * [--env=<env>]
   * : environment for which to fetch connection info
   *
   * @command columns
   */
  public function columns($args, $assoc_args) {
    $connect = $this->_openConnection($assoc_args);
    $columns = $this->_getBlobColumns($connect);

    if (!empty($columns)) {
      $return = [];
      foreach ($columns as $key => $value) {
        $table  = $value['TABLE_NAME'];
        $column = $value['COLUMN_NAME'];

        $query = "SELECT length($column)/1024 AS column_KB FROM $table LIMIT 1";
        if ($result = mysqli_query($connect, $query)) {
          $row = mysqli_fetch_row($result);
          if (!empty($row[0])) {
            $row = $row[0];
          } else {
            $row = 0;
          }
          mysqli_free_result($result);
        }

        $return[] = [
          'table' => $table,
          'column' => $column,
          'biggest_entry_(KB)' => $row,
        ];
      }
    }

    // Sorting based on biggest data.
    foreach ($return as $key => $value) {
      $biggest_entry[$key] = $value['biggest_entry_(KB)'];
    }

    array_multisort($biggest_entry, SORT_DESC, $return);

    $this->_closeConnection($connect);
    $this->output()->outputRecordList($return);
  }

  /**
   * Finds the biggest cells for a given table/column.
   *
   * ## OPTIONS
   *
   * [--site=<site>]
   * : name of the site
   *
   * [--env=<env>]
   * : environment for which to fetch connection info
   *
   * [--table=<table>]
   * : Table name with potentially large blob of data
   *
   * [--column=<col>]
   * : Column name with potentially large blob of data
   *
   * @command cells
   */
  public function cells($args, $assoc_args) {
    $connect = $this->_openConnection($assoc_args);

    if (empty($assoc_args['table']) || empty($assoc_args['column'])) {
      $this->log()->error('Please specify both the --column and --table parameters.');
      exit;
    }

    $table  = mysqli_real_escape_string($connect, $assoc_args['table']);
    $column = mysqli_real_escape_string($connect, $assoc_args['column']);

    $query = "SELECT column_name 
    FROM information_schema.columns 
    WHERE table_name = '$table' AND column_name != '$column'";

    if ($result = mysqli_query($connect, $query)) {
      $cols = [];
      while ($row = mysqli_fetch_row($result)) {
        $cols[] = $row[0];
      }
      mysqli_free_result($result);
    }

    $cols = implode(',', $cols);

    $query = "SELECT $cols, length($column)/1024 AS column_KB 
    FROM $table 
    ORDER BY column_KB DESC 
    LIMIT 50";

    if ($result = mysqli_query($connect, $query)) {
      while ($row = mysqli_fetch_assoc($result)) {
        $return[] = $row;
      }
      mysqli_free_result($result);
    }

    $this->_closeConnection($connect);
    $this->output()->outputRecordList($return);
  }

}
