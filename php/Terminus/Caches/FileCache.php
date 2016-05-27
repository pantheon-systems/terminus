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

namespace Terminus\Caches;

use Symfony\Component\Finder\Finder;
use Terminus\Exceptions\TerminusException;

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
   * Object constructor. Sets properties.
   *
   * @param array $arg_options Elements as follow:
   *  string cache_dir  The location of the cache
   *  int    ttl       The cache file's default expiry time
   *  int    maxSize   The max total cache size
   *  string whitelist A list of characters that are allowed in path
   */
  public function __construct(array $arg_options = []) {
    $default_options = [
      'cache_dir' => TERMINUS_CACHE_DIR,
      'ttl'       => 832040,
      'max_size'  => 267914296,
      'whitelist' => 'a-z0-9._-',
    ];
    $options = array_merge($default_options, $arg_options);

    $this->root      = rtrim($options['cache_dir'], '/\\') . '/';
    $this->ttl       = (int)$options['ttl'];
    $this->maxSize   = (int)$options['max_size'];
    $this->whitelist = $options['whitelist'];

    if (!file_exists($this->root)) {
      $this->enabled = false;
    }
  }

  /**
   * Clean cache based on time to live and max size
   *
   * @return bool True if cache clean succeeded
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

      $finder = $this->getFinder()->date(
        'before ' . $expire->format(TERMINUS_DATE_FORMAT)
      );
      foreach ($finder as $file) {
        unlink($file->getRealPath());
      }
    }

    // unlink older files if max cache size is exceeded
    if ($maxSize > 0) {
      $files = array_reverse(
        iterator_to_array(
          $this->getFinder()->sortByAccessedTime()->getIterator()
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
   * Flushes all caches
   *
   * @return void
   */
  public function flush() {
    $finder = $this->getFinder();
    foreach ($finder as $file) {
      unlink($file->getRealPath());
    }
  }

  /**
   * Reads retrieves data from cache
   *
   * @param string $key     A cache key
   * @param array  $options Elements as follows:
   *        [bool] decode_array Argument 2 for json_decode
   *        [bool] ttl          TTL for file read
   * @return bool|string The file contents or false
   */
  public function getData($key, array $options = []) {
    $defaults = [
      'decode_array' => false,
      'ttl'          => null,
    ];
    $options  = array_merge($defaults, $options);

    try {
      $contents = $this->read($key, $options['ttl']);
    } catch (\Exception $e) {
      return false;
    }

    $data = [];
    if ($contents) {
      $data = json_decode($contents, $options['decode_array']);
    }
    return $data;
  }

  /**
   * Returns the cache root
   *
   * @return string
   */
  public function getRoot() {
    return $this->root;
  }

  /**
   * Checks if a file is in cache and return its filename
   *
   * @param string $key Cache key
   * @param int    $ttl Time to live
   * @return bool|string The filename or false
   */
  public function has($key, $ttl = null) {
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
   * Returns whether cache is enabled
   *
   * @return bool
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * Saves data to the cache, JSON-encoded
   *
   * @param string $key  A cache key
   * @param mixed  $data Data to save to cache
   * @return bool True if write succeeded
   */
  public function putData($key, $data) {
    $json   = json_encode($data);
    $result = $this->write($key, $json);
    return $result;
  }

  /**
   * Remove file from cache
   *
   * @param string $key Cache key
   * @return bool
   */
  public function remove($key) {
    if (!$this->enabled) {
      return false;
    }

    $filename = $this->filename($key);

    if (file_exists($filename)) {
      $unlinking = unlink($filename);
      return (boolean)$unlinking;
    }
    return false;
  }

  /**
   * Filename from key
   *
   * @param string $key Key to validate
   * @return string
   */
  protected function filename($key) {
    $filename = $this->root . $this->validateKey($key);
    return $filename;
  }

  /**
   * Get a Finder that iterates in cache root only the files
   *
   * @return Finder
   */
  protected function getFinder() {
    $finder = Finder::create()->in($this->root)->files();
    return $finder;
  }

  /**
   * Reads from the cache file
   *
   * @param string  $key A cache key
   * @param integer $ttl The time to live
   * @return bool|string The file contents or false
   */
  protected function read($key, $ttl = null) {
    $filename = $this->has($key, $ttl);

    $data = false;
    if ($filename) {
      $data = file_get_contents($filename);
    }
    return $data;
  }

  /**
   * Validate cache key
   *
   * @param string $key A cache key
   * @return string A relative filename
   */
  protected function validateKey($key) {
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
   * Writes to cache file
   *
   * @param string $key      A cache key
   * @param string $contents The file contents
   * @return bool True if write was successful
   */
  protected function write($key, $contents) {
    $filename = TERMINUS_CACHE_DIR . "/$key";

    $written = false;
    if ($filename) {
      $written = (file_put_contents($filename, $contents) && touch($filename));
    }
    return $written;
  }

}
