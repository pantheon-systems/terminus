<?php
/**
 * Created by PhpStorm.
 * User: stovak
 * Date: 3/25/14
 * Time: 11:36 AM
 */

namespace Pantheon\Iterators;


class BackupList extends Response {

  protected $_headers = array(
    "uuid" => "ID",
    "type" => "Type",
    "timestamp" => "Date",
    "folder" => "Bucket",
    "size" => "Size"
  );

  protected $responseClass = "Backup";

  public function respond($assoc_args) {
    if (array_key_exists("download", $assoc_args)) {
      \Terminus::line("Downloading backup...");
      passthru("curl -OL \"{" . $this->findByName($bid)->getURL() . "}\"");
      return "Downloaded";
    }
    else {
      parent::respond($assoc_args);
    }
  }

}