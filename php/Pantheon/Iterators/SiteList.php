<?php
/**
 * Created by PhpStorm.
 * User: stovak
 * Date: 3/25/14
 * Time: 11:36 AM
 */

namespace Pantheon\Iterators;


class SiteList extends Response {

  protected $responseClass = "Site";
  protected $_headers = array(
    "name" => "Name",
    "owner" => "Owner",
    "service_level" => "Service Level",
    "uuid" => "Uuid"
  );

}