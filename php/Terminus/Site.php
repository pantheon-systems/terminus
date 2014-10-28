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

  public function environments() {
    return $this;
  }

  /**
   * Return environment object from site
   * @param $environment string required
   */
  public function environment($environment) {
    if (array_key_exists($environment,$this->environments)) {
      return $this->environments[$environment];
    }
    $env = EnvironmentFactory::load($this->id, $environment);
    $this->environments[$environment] = $env;
    return $this->environments[$environment];
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
