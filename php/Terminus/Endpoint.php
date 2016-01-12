<?php

namespace Terminus;

/**
 * Class for getting/setting an api endpoint from provided arguments
 *
 **/
class Endpoint {
  public $patterns = array(
    'deprecated'  => '/Terminus.php?%s=%s',
    'private'     => '/api/%s/%s',
    'public'      => '/api/%s',
    'login'       => '/api/authorize',
    'authorize'   => '/api/authorize/machine-token',
  );

  private $public_realms = array(
    'upstreams',
  );

  // Some "realms" are different on Hermes than on Terminus.php.
  public $realm_map = array(
    'user'    => 'users',
    'site'    => 'sites'
  );

  private $target = 'deprecated';

  /**
   * Object constructor. Sets target property to private.
   */
  public function __construct() {
    $this->target = 'private';
  }

  /**
   * This is a convoluted (but unit-tested) function to build the needed API
   * endpoint. Once we're fully committed to the 2.0 api we can clean it up a
   * bit.
   *
   * @param array $args Should contain a realm and uuid, can also have a path
   * @return string
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
  private function lookup($args) {
    // adjust the target if it's a public request
    if (isset($args['uuid']) && ($args['uuid'] == 'public')) {
      $this->target = 'public';
    }

    if (isset($args['realm']) && ($args['realm'] == 'login')) {
      $this->target = 'login';
    }

    if (isset($args['realm']) && ($args['realm'] == 'authorize')) {
      $this->target = 'authorize';
    }

    //A substiution array to pass to the vsprintf
    $substitutions = array($args['realm']);
    if (isset($args['uuid']) && $args['uuid'] != 'public') {
      array_push($substitutions, $args['uuid']);
    }

    $url = vsprintf($this->patterns[$this->target], $substitutions);

    //Now that we have our base url, we add the path
    if (isset($args['path']) && $args['path']) {
      $url .= '/' . $args['path'];
    }

    return $url;
  }

  /**
   * Retrieves an endpoint
   * @param array $args Elements as follow:
   *        [string] realm user, site, organization
   *        [string] path specific method to call
   * @return stringß
   */
  static function get($args) {
    $endpoint        = new Endpoint($args);
    $endpoint_string = $endpoint->lookup($args);
    return $endpoint_string;
  }

}
