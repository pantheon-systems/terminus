<?php

namespace Terminus\Helpers;

use Terminus\Exceptions\TerminusException;

/**
 * Class RequirementsHelper
 *
 * Contains useful helper functions to check system requirements
 */
class RequirementsHelper {
    /**
     * @var string
     */
  public static $min_php = '5.5.9';
    /**
     * @var string
     */
  public static $min_openssl = '1.0.1';

    /**
     * Run all requirements checks.
     *
     * @throws \Terminus\Exceptions\TerminusException
     *
     * @return void.
     */
  public static function validateEnvironment() {
    static::hasMinimumPhp();
    static::hasMinimumSsl();
  }

    /**
     * Validate that environment has a valid version of PHP.
     *
     * @param string|null $version PHP Version to compare if desired.
     *
     * @return bool
     * @throws \Terminus\Exceptions\TerminusException
     */
  public static function hasMinimumPhp( $version = null ) {
    if ($version != null) {
      $php_version = $version;
    } else {
      $php_version = PHP_VERSION;
    }

    if (version_compare(
      $php_version, static::$min_php,
      '<'
    ) ) {
      throw new TerminusException(
        "Error: Terminus requires PHP {min_php} or newer. You are running version {version}.",
        [
        'min_php' => static::$min_php,
        'version' => PHP_VERSION
                ],
        1
      );
    } else {
      return true;
    }
  }

    /**
     * Validate that environment uses a version of OpenSSL that supports >= TLS 1.1.
     *
     * @param string|null $version SSL version to check if desired.
     *
     * @return bool
     * @throws \Terminus\Exceptions\TerminusException
     */
  public static function hasMinimumSsl( $version = null ) {
    $ssl_version = static::getOpenSslVersionNumber($version);
    if (version_compare($ssl_version, static::$min_openssl, '<')) {
      throw new TerminusException(
        'SSL version is {version}, a minimum version of {min_version} is required.',
        [
        'version'     => $ssl_version,
        'min_version' => static::$min_openssl
                ]
      );
    } else {
      return true;
    }
  }

    /**
     * Convert OpenSSL constant to a value that is parsable by either a human
     * or using version_compare().
     *
     * @param string|null $openssl_version_number The version to check.
     * @param bool        $patch_as_number        whether to convert patch level to number.
     *
     * @return bool|string
     */
  public static function getOpenSslVersionNumber(
        $openssl_version_number = null,
        $patch_as_number = true
    ) {
    if (is_null($openssl_version_number)) {
      $openssl_version_number = OPENSSL_VERSION_NUMBER;
    }
    $openssl_numeric_identifier = str_pad(
      (string)dechex($openssl_version_number),
      8, '0', STR_PAD_LEFT
    );

    $openssl_version_parsed = [ ];
    $preg = '/(?<major>[[:xdigit:]])(?<minor>[[:xdigit:]][[:xdigit:]])';
    $preg .= '(?<fix>[[:xdigit:]][[:xdigit:]])';
    $preg .= '(?<patch>[[:xdigit:]][[:xdigit:]])(?<type>[[:xdigit:]])/';
    preg_match_all(
      $preg, $openssl_numeric_identifier,
      $openssl_version_parsed
    );
    $openssl_version = false;
    if (! empty($openssl_version_parsed)) {
      $alphabet        = [
      1  => 'a',
      2  => 'b',
      3  => 'c',
      4  => 'd',
      5  => 'e',
      6  => 'f',
      7  => 'g',
      8  => 'h',
      9  => 'i',
      10 => 'j',
      11 => 'k',
      12 => 'l',
      13 => 'm',
      14 => 'n',
      15 => 'o',
      16 => 'p',
      17 => 'q',
      18 => 'r',
      19 => 's',
      20 => 't',
      21 => 'u',
      22 => 'v',
      23 => 'w',
      24 => 'x',
      25 => 'y',
      26 => 'z'
      ];
      $openssl_version = intval($openssl_version_parsed['major'][0]) . '.';
      $openssl_version .= intval($openssl_version_parsed['minor'][0]) . '.';
      $openssl_version .= intval($openssl_version_parsed['fix'][0]);
      $patchlevel_dec = hexdec($openssl_version_parsed['patch'][0]);
      if (! $patch_as_number && array_key_exists(
        $patchlevel_dec,
        $alphabet
      )
      ) {
        $openssl_version .= $alphabet[ $patchlevel_dec ]; // ideal for text comparison
      } else {
        $openssl_version .= '.' . $patchlevel_dec; // ideal for version_compare
      }
    }

    return $openssl_version;
  }

}
