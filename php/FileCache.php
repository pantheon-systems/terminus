<?php

/*
 * This file is heavily inspired and use code from Composer(getcomposer.org),
 * in particular Composer/Cache and Composer/Util/FileSystem from 1.0.0-alpha7
 *
 * The original code and this file are both released under MIT license.
 *
 * The copyright holders of the original code are:
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 */

namespace Terminus;

use Symfony\Component\Finder\Finder;

/**
 * Reads/writes to a filesystem cache
 */
class FileCache {

  /**
   * @var string cache path
   */
  protected $root;
  /**
   * @var bool
   */
  protected $enabled = true;
  /**
   * @var int files time to live
   */
  protected $ttl = 36000;
  /**
   * @var int max total size
   */
  protected $maxSize;
  /**
   * @var string key allowed chars (regex class)
   */
  protected $whitelist;

  /**
   * @param string $cacheDir  location of the cache
   * @param int    $ttl       cache files default time to live (expiration)
   * @param int    $maxSize   max total cache size
   * @param string $whitelist List of characters that are allowed in path names (used in a regex character class)
   */
  public function __construct( $cacheDir, $ttl, $maxSize, $whitelist = 'a-z0-9._-' ) {
    $this->root      = rtrim($cacheDir, '/\\') . '/';
    $this->ttl       = (int)$ttl;
    $this->maxSize   = (int)$maxSize;
    $this->whitelist = $whitelist;

    if (!$this->ensure_dir_exists($this->root)) {
      $this->enabled = false;
    }

  }

  /**
   * Cache is enabled
   *
   * @return bool
   */
  public function is_enabled() {
    return $this->enabled;
  }

  /**
   * Cache root
   *
   * @return string
   */
  public function get_root() {
    return $this->root;
  }

  /**
   * Check if a file is in cache and return its filename
   *
   * @param string $key cache key
   * @param int    $ttl time to live
   * @return bool|string filename or false
   */
  public function has( $key, $ttl = null ) {
    if (!$this->enabled) {
      return false;
    }

    $filename = $this->filename($key);

    if (!file_exists($filename)) {
      return false;
    }

    // use ttl param or global ttl
    if ($ttl === null) {
      $ttl = $this->ttl;
    } elseif ($this->ttl > 0) {
      $ttl = min((int)$ttl, $this->ttl);
    } else {
      $ttl = (int)$ttl;
    }

    //
    if ($ttl > 0 && filemtime($filename) + $ttl < time()) {
      if ($this->ttl > 0 && $ttl >= $this->ttl) {
        unlink($filename);
      }
      return false;
    }

    return $filename;
  }

  /**
   * Writes to cache file
   *
   * @param [string] $key      A cache key
   * @param [string] $contents The file contents
   * @return [boolean]
   */
  public function write( $key, $contents ) {
    $filename = $this->prepare_write($key);

    if ($filename) {
      return file_put_contents($filename, $contents) && touch($filename);
    } else {
      return false;
    }
  }

  public function put_data( $key, $array ) {
    $json   = json_encode($array);
    $result = $this->write($key, $json);
    return $result;
  }

  /**
   * Read from cache file
   *
   * @param [string]  $key A cache key
   * @param [integer] $ttl The time to live
   * @return [boolean|string] The file contents or false
   */
  public function read( $key, $ttl = null ) {
    $filename = $this->has($key, $ttl);

    if ($filename) {
      return file_get_contents($filename);
    } else {
      return false;
    }
  }

  public function get_data($key, $options = array()) {
    $defaults = array(
      'decode_array' => false,
      'ttl' => null
    );
    $options  = array_merge($defaults, $options);

    $contents = $this->read($key, $options['ttl']);

    if ($contents) {
      return json_decode($contents, $options['decode_array']);
    }
    else {
      return false;
    }
  }

  /**
   * Copy a file into the cache
   *
   * @param string $key    cache key
   * @param string $source source filename
   * @return bool
   */
  public function import( $key, $source ) {
    $filename = $this->prepare_write($key);

    if ($filename) {
      return copy($source, $filename) && touch($filename);
    } else {
      return false;
    }
  }

  /**
   * Copy a file out of the cache
   *
   * @param [string]  $key    cache key
   * @param [string]  $target target filename
   * @param [integer] $ttl    time to live
   * @return bool
   */
  public function export( $key, $target, $ttl = null ) {
    $filename = $this->has($key, $ttl);

    if ($filename) {
      return copy($filename, $target);
    } else {
      return false;
    }
  }

  /**
   * Remove file from cache
   *
   * @param [string] $key cache key
   * @return [boolean]
   */
  public function remove($key) {
    if (!$this->enabled) {
      return false;
    }

    $filename = $this->filename($key);

    if (file_exists($filename)) {
      $unlinking = unlink($filename);
      return $unlinking;
    } else {
      return false;
    }
  }

  /**
   * Clean cache based on time to live and max size
   *
   * @return bool
   */
  public function clean() {
    if (!$this->enabled) {
      return false;
    }

    $ttl     = $this->ttl;
    $maxSize = $this->maxSize;

    // unlink expired files
    if ($ttl > 0) {
      $expire = new \DateTime();
      $expire->modify('-' . $ttl . ' seconds');

      $finder = $this->get_finder()->date(
        'until ' . $expire->format('Y-m-d H:i:s')
      );
      foreach ($finder as $file) {
        unlink($file->getRealPath());
      }
    }

    // unlink older files if max cache size is exceeded
    if ($maxSize > 0) {
      $files = array_reverse(
        iterator_to_array(
          $this->get_finder()->sortByAccessedTime()->getIterator()
        )
      );
      $total = 0;

      foreach ($files as $file) {
        if ($total + $file->getSize() <= $maxSize) {
          $total += $file->getSize();
        } else {
          unlink($file->getRealPath());
        }
      }
    }

    return true;
  }

  /**
   * Ensures a directory exists
   *
   * @param [string] $dir Directory to ensure existence of
   * @return [boolean] $dir_exists
   */
  protected function ensure_dir_exists($dir) {
    $dir_exists = (
      is_dir($dir)
      || !(file_exists($dir) && mkdir($dir, 0777, true))
    );
    return $dir_exists;
  }

  /**
   * Prepare cache write
   *
   * @param [string] $key A cache key
   * @return [bool|string] A filename or false
   */
  protected function prepare_write($key) {
    if (!$this->enabled) {
      return false;
    }
    $filename = $this->filename($key);
    if (!$this->ensure_dir_exists(dirname($this->filename($key)))) {
      return false;
    }
    return $filename;
  }

  /**
   * Validate cache key
   *
   * @param [string] $key A cache key
   * @return [string] $parts_string A relative filename
   */
  protected function validate_key($key) {
    $url_parts = parse_url($key);
    if (! empty($url_parts['scheme'])) { // is url
      $parts = array('misc');

      $part_parts = array($url_parts['scheme'] . '-');
      if (isset($url_parts['host'])) {
        $part_parts[] = $url_parts['host'];
      }
      if (!empty($url_parts['port'])) {
        $part_parts[] = '-' . $url_parts['port'];
      }
      $parts[] = implode('', $part_parts);

      $part_parts = array(substr($url_parts['path'], 1));
      if (!empty($url_parts['query'])) {
        $part_parts[] = '-' . $url_parts['query'];
      }
      $parts[] = implode('', $part_parts);
    } else {
      $key   = str_replace('\\', '/', $key);
      $parts = explode('/', ltrim($key));
    }
    $parts = preg_replace("#[^{$this->whitelist}]#i", '-', $parts);

    $parts_string = implode('/', $parts);
    return $parts_string;
  }

  /**
   * Filename from key
   *
   * @param [string] $key Key to validate
   * @return [string] $filename
   */
  protected function filename($key) {
    $filename = $this->root . $this->validate_key($key);
    return $filename;
  }

  /**
   * Get a Finder that iterates in cache root only the files
   *
   * @return [Finder] $finder
   */
  public function get_finder() {
    $finder = Finder::create()->in($this->root)->files();
    return $finder;
  }

  /**
   * Flushes all caches
   *
   * @return [void]
   */
  public function flush() {
    $finder = $this->get_finder();
    foreach ($finder as $file) {
      unlink($file->getRealPath());
    }
  }

}
