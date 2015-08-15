<?php
namespace Terminus;

use \Terminus\Utils;
/**
 * Class for getting/setting an api endpoint from provided arguments
 *
 **/
class Endpoint {
  public $patterns = array(
    'deprecated' => 'https://%s/terminus.php?%s=%s',
    'private'  => 'https://%s/api/%s/%s',
    'public'   => 'https://%s/api/%s',
    'login'    => 'https://%s/api/authorize',
  );

  // some "realms" are different on hermes then terminus.php, this is a
  // simple has index to migrate them
  public $realm_map = array(
    'user'    => 'users',
    'site'    => 'sites'
  );

  private $public_realms = array(
    'upstreams',
  );

  private $target = 'deprecated';

  public function __construct()
  {
      $this->target = 'private';
  }

  /**
   * This is a convoluted (but unit tested) function to build the needed api
   * endpoint. Once we're fully committed to the 2.0 api we can clean it up a
   * bit.
   *
   * @package CLI
   * @version 1.5
   * @param $args (array) - should contain at least a realm and uuid, can also have a path
   *
   *    Example:
   *
   *    $args = array(
   *      'realm' => 'users',
   *      'uuid'  => 'c4912ef3-2ec0-400d-906d-02d9fd035b98',
   *      'path'  => 'sites',
   *    );
   *
   */
   private function lookup( $args )
  {
    // adjust the target if it's a public request
    if ( isset($args['uuid']) AND 'public' === $args['uuid'] ) {
      $this->target = 'public';
    }

    if ('login' == $args['realm']) {
      $this->target = 'login';
    }

    $args['host'] = @$args['host'] ?: TERMINUS_HOST;

    // a substiution array to pass to the vsprintf
    $substitutions = array( $args['host'], $args['realm'] );
    if( isset($args['uuid']) AND $args['uuid'] !== 'public' ) {
      array_push( $substitutions, $args['uuid'] );
    }

    $url = vsprintf( $this->patterns[$this->target], $substitutions );

    // now we have our base url add the path
    $params = '';
    if (@$args['path']) {
      $params .=  '/' . @$args['path'];
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
