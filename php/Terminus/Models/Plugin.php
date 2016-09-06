<?php

namespace Terminus\Models;

use Terminus\Config;
use Terminus\Exceptions\TerminusException;

class Plugin extends TerminusModel {
  /**
   * @var string
   */
  private $path_name;

  /**
   * Object constructor
   *
   * @param object $attributes Attributes of this model
   * @param array  $options    Options with which to configure this model
   */
  public function __construct($attributes, array $options = []) {
    parent::__construct($attributes, $options);
    $this->path_name = sprintf(
      '%s/%s',
      Config::get('plugins_dir'),
      $this->get('slug')
    );
  }

  /**
   * Formats workflow object into an associative array for output
   *
   * @return array Associative array of data for output
   */
  public function serialize() {
    $data = [
      'name' => $this->get('name'),
      'author' => $this->get('author'),
      'description' => $this->get('description'),
      'keywords' => $this->get('keywords'),
      'is_installed' => $this->isInstalled(),
    ];
    return $data;
  }

  /**
   * Installs a plugin to the user's system
   *
   * @return integer The status code of the installation
   */
  public function install() {
    $temp_file_name = '/tmp/' . uniqid() . '.zip';
    //Download the plugin archive
    file_put_contents(
      $temp_file_name,
      fopen($this->get('archive_url'), 'r')
    );

    //Decompress the archive
    $command = "unzip $temp_file_name -d $this->path_name.tmp";
    exec($command, $output, $status_code);
    if ($status_code > 0) {
      throw new TerminusException(
        "Encountered an error while unzipping the archive: {output}",
        compact('output'),
        $status_code
      );
    }

    //Move the internal directory up to where it belongs
    $command = "mv $this->path_name.tmp/{$this->get('slug')}-master $this->path_name";
    exec($command, $output);

    //Remove the compresed archive file and temp dir
    unlink($temp_file_name);
    $command = "rm -r $this->path_name.tmp";
    exec($command, $output);

    return $status_code;
  }

  /**
   * Determines whether this plugin is currently installed
   *
   * @return boolean
   */
  public function isInstalled() {
    $is_installed = file_exists("{$this->path_name}/composer.json");
    return $is_installed;
  }

  /**
   * Uninstalls a plugin from the user's system
   *
   * @return integer The status code of the uninstallation
   */
  public function uninstall() {
    exec("rm -r {$this->path_name}", $output, $status_code);
    return $status_code;
  }

}
