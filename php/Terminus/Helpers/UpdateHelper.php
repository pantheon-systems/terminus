<?php

namespace Terminus\Helpers;

use Terminus\Caches\FileCache;
use Terminus\Config;
use Terminus\Request;

class UpdateHelper extends TerminusHelper
{
    protected $update_notice = <<<EOT
A new Terminus version v{new_version} is available. You are currently using version v{old_version}. 

PLEASE NOTE:
Terminus version v1.0 and later introduces a new command line and argument structure that is
incompatible with any custom scripts that use terminus or older plugins that you may be using.

PLEASE CONSIDER THE IMPACT TO YOUR AUTOMATION SCRIPTS AND PLUGIN DEPENDENCIES BEFORE UPGRADING.

Terminus users will benefit from the new simplified and consistent command structure we have
created for you in v1.0. We have prepared an upgrade guide to assist you in learning the
differences and improvements we have made to Terminus:
{{guide_url}}
EOT;

    protected $guide_url = 'https://pantheon.io/docs/terminus/commands/compare/';

   /**
   * Retrieves current version number from repository and saves it to the cache
   *
   * @return string The version number
   */
    public function getCurrentVersion()
    {
        $request  = new Request();
        $url = 'https://api.github.com/repos/pantheon-systems/terminus/releases/latest';
        $release = $request->request($url, ['absolute_url' => true,])['data'];
        $cache = new FileCache();
        $cache->putData(
            'latest_release',
            ['version' => $release->name, 'check_date' => time(),]
        );
        return $release->name;
    }

  /**
   * Checks for new versions of Terminus once per week and saves to cache
   *
   * @return void
   */
    public function checkForUpdate()
    {
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
                $my_version = Config::get('version');
                if (version_compare($current_version, $my_version, '>')) {
                    $this->command->log()->info(
                        $this->update_notice,
                        ['new_version' => $current_version,
                         'old_version' => $my_version,
                         'guide_url' => $this->guide_url]
                    );
                }
            } catch (\Exception $e) {
                $this->command->log()->debug(
                    "Cannot retrieve current Terminus version.\n{msg}",
                    ['msg' => $e->getMessage(),]
                );
            }
        }
    }
}
