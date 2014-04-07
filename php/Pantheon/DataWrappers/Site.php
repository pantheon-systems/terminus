<?php

namespace Pantheon\DataWrappers;


class Site extends RestRemote {

  protected $created;
  protected $name;
  protected $owner;
  protected $preferred_zone;
  protected $service_level;
  protected $label;
  protected $metadata;
  protected $_headers = array(
    "name" => "Name",
    "owner" => "Owner",
    "service_level" => "Service Level",
    "uuid" => "Uuid"
  );


  function __construct($raw) {
    if (property_exists($raw, "information")) {
      foreach ($raw->information as $key => $value) {
        $this->$key = $value;
      }
    }
    if (property_exists($raw, "metadata")) {
      $this->metadata = $raw->metadata;
    }
    if (property_exists($raw, "uuid")) {
      $this->uuid = $raw->uuid;
    }
  }

  public function getTableRow(array $columns) {
    $user = @User::fromUUID($this->owner);
    return array(
      $this->getName(),
      $user->__toString(),
      $this->service_level,
      $this->uuid
    );
  }

}