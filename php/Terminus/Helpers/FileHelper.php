<?php

namespace Terminus\Helpers;

use Terminus\Exceptions\TerminusException;
use Terminus\Helpers\TerminusHelper;

class FileHelper extends TerminusHelper {

  /**
   * Ensures that the given destination is valid
   *
   * @param string $destination Location of directory to ensure viability of
   * @param bool   $make        True to create destination if it does not exist
   * @return string Same as the parameter
   * @throws TerminusException
   */
  public function destinationIsValid($destination, $make = true) {
    if (file_exists($destination) AND !is_dir($destination)) {
      throw new TerminusException(
        'Destination given is a file. It must be a directory.'
      );
    }

    if (!is_dir($destination)) {
      if ($make) {
        mkdir($destination, 0755);
      }
    }

    return $destination;
  }

  /**
   * Get file name from a URL
   *
   * @param string $url A valid URL
   * @return string The file name from the given URL
   */
  public function getFilenameFromUrl($url) {
    $path     = $this->parseUrl($url);
    $parts    = explode('/', $path['path']);
    $filename = end($parts);
    return $filename;
  }

  /**
   * Loads a file of the given name from the assets directory.
   *
   * @param string $file Relative file path from the assets dir
   * @return string Contents of the asset file
   * @throws TerminusException
   */
  public function loadAsset($file) {
    $asset_location = sprintf('%s/assets/%s', TERMINUS_ROOT, $file);
    /**
    * The warning reporting is disabled because missing files will both issue
    * warnings and return false, and we cannot just catch the warning such as
    * things are currently set.
    */
    error_reporting(E_ALL ^ E_WARNING);
    $asset_file = file_get_contents($asset_location);
    error_reporting(E_ALL);

    if (!$asset_file) {
      throw new TerminusException(
        'Terminus could not locate an asset file at {asset_location}',
        compact('asset_location'),
        1
      );
    }
    return $asset_file;
  }

  /**
   * Removes ".gz" from a filename
   *
   * @param string $filename Name of file from which to remove ".gz"
   * @return string Param string, ".gz" removed
   */
  public function sqlFromZip($filename) {
    $file = preg_replace('#\.gz$#s', '', $filename);
    return $file;
  }

  /**
   * Parses a URL and returns its components
   *
   * @param string $url URL to parse
   * @return array An array of URL components
   */
  private function parseUrl($url) {
    $url_parts = parse_url($url);

    if (!isset($url_parts['scheme'])) {
      $url_parts = parse_url('http://' . $url);
    }

    return $url_parts;
  }

}
