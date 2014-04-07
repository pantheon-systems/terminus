<?php
/**
 * Created by PhpStorm.
 * User: stovak
 * Date: 3/25/14
 * Time: 11:36 AM
 */

namespace Pantheon\Iterators;


class HostnameList extends Response {
  
  protected $_headers = array("name" => "Hostname");

  protected $responseClass = "Hostname";
  
}