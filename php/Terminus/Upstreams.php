<?php

namespace Terminus;

class Upstreams {
  public $upstreams;
  public $cache;
  public $type;
  public $category;
  public $framework;

  private function __construct() {
  }

  private function hydrate($args) {
    foreach($args as $key => $value) {
      if(property_exists($this, $key)) {
        $this->$key = $value;
      }
    }
  }

  public function query($args=null) {
    if ($args)
      $this->hydrate($args);
    $this->loadUpstreams();
    return $this->upstreams;
  }

  private function loadUpstreams() {
    $key = join("-", array( $this->type, $this->category, $this->framework ) );
    $response = \TerminusCommand::request("products", "public", false, "GET");
    $upstreams = array();
    $keys_to_show = array('longname','framework','type','category');
    // we'll use this to sort the list later
    $sort = array();
    foreach( (array) $response['data'] as $id=>$details ) {
      if (!empty($this->type) AND $details->attributes->type != $this->type) {
        continue;
      }
      if (!empty($this->category) AND $details->attributes->category != $this->category) {
        continue;
      }
      if (!empty($this->framework) AND $details->attributes->framework != $this->framework) {
        continue;
      }

      $sort[] = $details->attributes->shortname;
      $row = array();
      $row['id'] = $id;
      foreach( $keys_to_show as $key ) {
        $row[$key] = @$details->attributes->$key;
      }
      array_push($upstreams, $row);
    }
    array_multisort( $sort, SORT_ASC, SORT_REGULAR, $upstreams);
    $this->upstreams = $upstreams;
    return $upstreams;
  }

  public static function get( $force_array = false ) {

    $upstreams = self::instance();
    $upstreams->query();
    if( $force_array ) {
      $array = array();
      foreach( $upstreams->upstreams as $upstream) {
        $array[] = (array)$upstream;
      }
      return $array;
    }

    return $upstreams->upstreams;
  }

  public static function selectList() {
    $upstreams = self::get(TRUE);
    $select = array();
    foreach( $upstreams as $upstream ) {
      $select[] = $upstream['longname'];
    }
    return $select;
  }

  public static function getByIndex( $index ) {
    $upstream = self::get(TRUE);
    return $upstream[$index];
  }

  /**
   * Search available upstreams by $id
   * @param $id string - expects valid uuid-format. i.e. e8fe8550-1ab9-4964-8838-2b9abdccf4bf
   *
   * @return upstream array
   */
  public static function getById($id) {
    $upstreams = self::get(TRUE);
    foreach ($upstreams as $upstream) {
      if ($upstream['id'] == $id) {
        return $upstream;
      }
    }
    return false;
  }

  /**
   * Search available upstreams by $id
   * @param $id_or_name string - can be $id or name
   *
   * @return upstream array
   */
  public static function getByIdOrName($id_or_name) {
    $upstreams = self::get(TRUE);
    foreach ($upstreams as $upstream) {
      if ($upstream['id'] == $id_or_name) {
        return $upstream;
      }
      if (strtolower($upstream['longname']) == strtolower($id_or_name)) {
        return $upstream;
      }
    }
    return false;
  }


  public static function instance() {
    static $instance;
    if ( null === $instance ) {
      $instance = new self();
    }
    return $instance;
  }

}
