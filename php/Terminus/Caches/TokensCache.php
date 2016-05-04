<?php

namespace Terminus\Caches;

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
    if (!$this->tokenExistsForEmail($email)) {
      throw new TerminusException(
        'There is no saved token for the account with email address {email}.',
        compact('email'),
        1
      );
    }
    $file_name = $this->getCacheDir() . "/$email";
    $contents = (array)json_decode(file_get_contents($file_name));
    return $contents;
  }

  /**
   * Returns a list of all emails with saved tokens
   *
   * @return string[]
   */
  public function getAllSavedTokenEmails() {
    $dir_files = array_diff(scandir($this->getCacheDir()), array('..', '.'));
    $files     = [];
    foreach ($dir_files as $file) {
      $files[] = str_replace($this->getCacheDir() . '/', '', $file);
    }
    return $files;
  }

  /**
   * Determines the tokens cache directory
   *
   * @return string
   */
  public function getCacheDir() {
    return TERMINUS_TOKENS_DIR;
  }

  /**
   * Checks to see whether the email has been set with a machine token
   *
   * @param string $email Email address to check for
   * @return bool
   */
  public function tokenExistsForEmail($email) {
    $file_name   = $this->getCacheDir() . "/$email";
    $file_exists = file_exists($file_name);
    return $file_exists;
  }

}
