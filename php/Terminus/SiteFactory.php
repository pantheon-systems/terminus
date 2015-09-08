<?php
namespace Terminus;
use Terminus\Site;
use Terminus\Session;
use \TerminusCommand;

class SiteFactory {
  private static $instance = null;
  private $sites = array();
  public $sitesCache;

  public function __construct() {
    $this->sitesCache = new SitesCache();
    $this->hydrate();
    return $this;
  }

  private function hydrate() {
    $sites = $this->sitesCache->rebuild();
    foreach ($sites as $site_name => $site_data) {
      // we need to skip sites that are in the build process still
      // Not sure about this with the new SiteCache API
      // if (!isset($site_data->information)) continue;
      $this->sites[$site_name] = new Site((object)$site_data);
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
      throw new \Exception(sprintf('Cannot find site with the name "%s"', $sitename));
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
