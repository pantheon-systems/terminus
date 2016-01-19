<?php

namespace Terminus;

use Terminus\Exceptions\TerminusException;

/**
 * Saves machine tokens to the home directory for later use
 */
class TokensCache {

  /**
   * Adds a record for a machine token to the tokens cache. Records should
   * be comprised of a JSON object with both the email and token.
   *
   * @param string[] $token_data Elements as follow:
   *   string email Email address for the account associated with the token
   *   string token Token to be saved
   * @return bool
   */
  public function add(array $token_data = []) {
    $file_name = $this->getCacheDir() . '/' . $token_data['email'];
    $status    = (boolean)file_put_contents(
      $file_name,
      json_encode($token_data)
    );
    return $status;
  }

  /**
   * Finds the site of the given attributes within the cache
   *
   * @param  string $email Email address keyed to the token to retrieve
   * @return string[]
   * @throws TerminusException
   */
  public function findByEmail($email) {
    $file_name = $this->getCacheDir() . "/$email";
    if (!file_exists($file_name)) {
      throw new TerminusException(
        'There is no saved token for the account with email address {email}.',
        compact('email'),
        1
      );
    }
    $contents = (array)json_decode(file_get_contents($file_name));
    return $contents;
  }

  /**
   * Determines the tokens cache directory
   *
   * @return string
   */
  private function getCacheDir() {
    $home = getenv('HOME');
    if (!$home) {
      // Sometimes in Windows, $HOME is not defined
      $home = getenv('HOMEDRIVE') . '/' . getenv('HOMEPATH');
    }
    $dir = getenv('TERMINUS_TOKENS_DIR');
    if (!$dir) {
      $dir = "$home/.terminus/tokens";
    }
    if (!file_exists($dir)) {
      mkdir($dir);
    }
    return $dir;
  }

}
