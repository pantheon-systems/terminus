<?php
namespace Terminus;

use \Terminus\Utils;
/**
 * Class for getting/setting an api endpoint from provided arguments
 *
 **/
class Endpoint {
  public $patterns = array(
      'terminus' => 'https://%s/terminus.php?%s=%s',
      'hermes'   => 'https://%s/api/%s/%s'
    );

  // some "realms" are different on hermes then terminus.php, this is a
  // simple has index to migrate them
  public $realm_map = array(
    'user'    => 'users',
    'site'    => 'sites'
  );

  private $target = 'terminus';

  public function __construct( )
  {
    if ( \Terminus\Utils\is_hermes() ) {
      $this->target = 'hermes';
    }
  }

  private function lookup( $args )
  {
    $args['host'] = @$args['host'] ?: TERMINUS_HOST;
    if ( array_key_exists(@$args['realm'],$this->realm_map) AND \Terminus\Utils\is_hermes() ) {
      $args['realm'] = $this->realm_map[$args['realm']];
    }
    $url = sprintf( $this->patterns[$this->target], @$args['host'], @$args['realm'], @$args['uuid'] );
    $params = '';
    if (@$args['path']) {
      $params .= ( 'terminus' === $this->target ) ? "&path=".urlencode($args['path']) : '/'.@$args['path'] ;
    }
    $url .= $params;
    return $url;
  }

  /**
   * @param $args (array)
   *    required args are
   *      - realm ( i.e. user,site,organization )
   *      - path ( specific method to call )
   */
  static function get( $args )
  {
    $endpoint = new Endpoint( $args );
    return $endpoint->lookup( $args );
  }

}
