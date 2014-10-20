<?php


class Products_Command extends Terminus_Command {
  public $products;
  public $cache;
  public $type;
  public $category;
  public $framework;

  public function __construct( ) {
    # Load commonly used data from cache.
    $this->cache = Terminus::get_cache();
    $this->loadProducts();
  }
  /**
  ## OPTIONS

  **/
  public function all( $args = array(), $assoc_args = array()) {
    if (isset($assoc_args['nocache'])) {
      $this->cache->put_data('products','');
    }

    $this->type = @$assoc_args['type'] ?: '';
    $this->category = @$assoc_args['category'] ?: '';
    $this->framework = @$assoc_args['framework'] ?: '';

    $this->loadProducts();
    $this->_constructTableForResponse( $this->products );
    return $this->products;
  }

  private function loadProducts() {
    $key = join("-", array( @$this->type, @$this->category, @$this->framework ) );
    if( !$products = $this->cache->get_data("products$key") ) {
      $response = $this->terminus_request("products", "public", false, "GET");
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
      $this->cache->put_data("products$key", $products);
    }
    $this->products = $products;
    return $products;
  }

  public static function get( $force_array = false ) {

    $products = self::instance();

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
    $products = self::get( TRUE );
    return $products[$index];
  }

  public static function instance() {
    static $instance;
    if ( null === $instance ) {
      $instance = new Self();
    }
    return $instance;
  }

}
Terminus::add_command( 'products', 'Products_Command' );
