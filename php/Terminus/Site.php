<?php
namespace Terminus;
use Terminus\Request;


class Site {
  public $id;
  public $information;
  public $metadata;
  public $environments = array();

  /**
   * Needs site object from the api to instantiate
   * @param $site (object) required - api site object
   */
  public function __construct($site) {
    $this->id = $site->id;
    $this->information = $site->information;
    // cosmetic reasons for this
    $this->information->id = $this->id;
    $this->metadata = @$site->metadata ?: new \stdClass();

    return $this;
  }

  /**
   * Return all environments for a site
   */
  public function environments() {
    $cache = \Terminus::get_cache();
    if (empty($this->environments)) {
      if (!$environments = $cache->get_data("environments:{$this->id}")) {
        $results = \Terminus_Command::request("sites", $this->getId(), "environments", "GET");
        $environments = $results['data'];
        $cache->put_data("environments:{$this->id}",$environments);
      }
      $this->environments = $environments;
    }

    // instantiate local objects
    foreach ( $this->environments as $name => $env) {
      $this->environments->$name = EnvironmentFactory::load($this->getId(), $name, array(
        'hydrate_with' => $env,
      ));
    }

    return $this->environments;
  }

  /**
   * Return environment object from site
   * @param $environment string required
   */
  public function environment($environment) {
    if (array_key_exists($environment,$this->environments)) {
      return $this->environments->$environment;
    } else {
      // load the environments
      $this->environments();
    }
    return $this->environments->$environment;
  }

  /**
   * Load site info
   */
  public function info() {
    return $this->information;
  }

  /**
   * Return site id
   */
   public function getId() {
     return $this->id;
   }

  /**
   * Return site name
   */
   public function getName() {
     return $this->information->name;
   }

}
