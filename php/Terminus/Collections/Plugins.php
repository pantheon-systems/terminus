<?php

namespace Terminus\Collections;

use Symfony\Component\Yaml\Yaml;
use Terminus\Config;
use Terminus\Exceptions\TerminusException;

class Plugins extends TerminusCollection {
  /**
   * @var string
   */
  protected $collected_class = 'Terminus\Models\Plugin';
  /**
   * @var string
   */
  private $plugins_list = '/config/plugins.yml';

  /**
   * Retrieves the model of the given ID, name, or slug
   *
   * @param string $id Identifier of desired plugin
   * @return Plugin
   * @throws TerminusException
   */
  public function get($id) {
    $plugins = $this->getMembers();
    foreach ($plugins as $plugin) {
      if (in_array(
        $id,
        [$plugin->id, $plugin->get('name'), $plugin->get('slug')]
      )) {
        return $plugin;
      }
    }
    throw new TerminusException(
      'Could not find {model} "{id}"',
      ['model' => $this->collected_class, 'id' => $id,]
    );
  }

  /**
   * Fetches model data from API and instantiates its model instances
   *
   * @param array $options Parameters to pass into the URL request
   * @return Plugins $this
   */
  public function fetch(array $options = []) {
    $plugins_list = $this->getCollectionData($options);

    foreach ($plugins_list as $id => $plugin_array) {
      $plugin = (object)$plugin_array;
      $plugin->id = $id;
      $this->add($plugin);
    }
    return $this;
  }

  /**
   * Retrieves collection data from the plugins file
   *
   * @param array $options Options for the ancestor class
   * @return array
   */
  protected function getCollectionData($options = []) {
    $plugins_list = Yaml::parse(
      file_get_contents(Config::get('root') . $this->plugins_list)
    );
    return $plugins_list;
  }

}
