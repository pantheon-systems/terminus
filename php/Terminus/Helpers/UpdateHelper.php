<?php

namespace Terminus\Helpers;

use Terminus\Caches\FileCache;
use Terminus\Helpers\TerminusHelper;
use Terminus\Request;

class UpdateHelper extends TerminusHelper {

  /**
   * Retrieves current version number from repository and saves it to the cache
   *
   * @return string The version number
   */
  public function getCurrentVersion() {
    $request  = new Request();
    $url      = 'https://api.github.com/repos/pantheon-systems/terminus/releases';
    $url     .= '?per_page=1';
    $response = $request->request($url, ['absolute_url' => true]);
    $release  = array_shift($response['data']);
    $cache    = new FileCache();
    $cache->putData(
      'latest_release',
      ['version' => $release->name, 'check_date' => time()]
    );
    return $release->name;
  }

  /**
   * Checks for new versions of Terminus once per week and saves to cache
   *
   * @return void
   */
  public function checkForUpdate() {
    $cache      = new FileCache();
    $cache_data = $cache->getData(
      'latest_release',
      ['decode_array' => true]
    );
    if (!$cache_data
        || ((int)$cache_data['check_date'] < (int)strtotime('-7 days'))
    ) {
      try {
        $current_version = $this->getCurrentVersion();
        if (version_compare($current_version, TERMINUS_VERSION, '>')) {
          $this->command->log()->info(
            'An update to Terminus is available. Please update to {version}.',
            ['version' => $current_version]
          );
        }
      } catch (TerminusException $e) {
        $this->command->log()->info(
          "Cannot retrieve current Terminus version.\n{msg}",
          $e->getReplacements()
        );
      }
    }
  }

}