<?php
namespace Terminus;

class Products {
  public $products;
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
    $this->loadProducts();
    return $this->products;
  }

  private function loadProducts() {
    $key = join("-", array( $this->type, $this->category, $this->framework ) );
    $response = \Terminus_Command::request("products", "public", false, "GET");
    $products = array();
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
      array_push($products, $row);
    }
    array_multisort( $sort, SORT_ASC, SORT_REGULAR, $products);
    $this->products = $products;
    return $products;
  }

  public static function get( $force_array = false ) {

    $products = self::instance();
    $products->query();
    if( $force_array ) {
      $array = array();
      foreach( $products->products as $product ) {
        $array[] = (array) $product;
      }
      return $array;
    }

    return $products->products;
  }

  public static function selectList() {
    $products = self::get(TRUE);
    $select = array();
    foreach( $products as $product ) {
      $select[] = $product['longname'];
    }
    return $select;
  }

  public static function getByIndex( $index ) {
    $products = self::get(TRUE);
    return $products[$index];
  }

  public static function getById($id) {
    $products = self::get(TRUE);
    foreach ($products as $product) {
      if ($product['id'] == $id) {
        return $product;
      }
    }
  }

  public static function instance() {
    static $instance;
    if ( null === $instance ) {
      $instance = new self();
    }
    return $instance;
  }

}
