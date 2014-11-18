<?php
namespace Terminus;
use Terminus\Site;
use Terminus\Session;
use \Terminus_Command;

class SiteFactory {
  private static $instance = null;
  private $sites;

  public function __construct() {
    $this->hydrate();
    return $this;
  }

  private function hydrate() {
    $cache = \Terminus::get_cache();
    $request = Terminus_Command::request( 'users', Session::getValue('user_uuid'), 'sites', 'GET', Array('hydrated' => true) );
    $sites = $request['data'];
    foreach( $sites as $site_id => $site_data ) {
      $site_data->id = $site_id;
      $this->sites[$site_data->information->name] = $site_data;
    }

    return $this;
  }

  public static function instance($sitename = null) {
    if (!self::$instance) {
      self::$instance = new self();
    }

    $factory = self::$instance;

    if ($sitename) {
      return $factory->getSite($sitename);
    } else {
      return $factory->getAll();
    }
  }

  public function getSite($sitename) {
    if (!array_key_exists($sitename,$this->sites)) {
      throw new \Exception('No site exists with this name');
    }
    if (isset($this->sites[$sitename])) {
      // if we haven't instatiated yet, do that now
      if ("Terminus\Site" != get_class($this->sites[$sitename])) {
        $this->sites[$sitename] = new Site($this->sites[$sitename]);
      }
      return $this->sites[$sitename];
    }
    return false;
  }

  public function getAll() {
    return $this->sites;
  }
}
