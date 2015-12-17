<?php

namespace Terminus;

class DocParser {

  protected $docComment;

  /**
   * Object constructor
   *
   * @param string $docComment Will be undecorated and saved
   * @return DocParser
   */
  function __construct($docComment) {
    $this->docComment = $this->removeDecorations($docComment);
  }

  /**
   * Gives short description plus more information
   *
   * @return string
   */
  public function getLongdesc() {
    $shortdesc = $this->getShortdesc();
    if (!$shortdesc) {
      return '';
    }

    $longdesc = substr($this->docComment, strlen($shortdesc));

    $lines = array();
    foreach (explode("\n", $longdesc) as $line) {
      if (0 === strpos($line, '@')) {
        break;
      }

      $lines[] = $line;
    }
    $longdesc = trim(implode($lines, "\n"));

    return $longdesc;
  }

  /**
   * Parses the doc comment to find short description
   *
   * @return string
   */
  public function getShortdesc() {
    if (!preg_match('|^([^@][^\n]+)\n*|', $this->docComment, $matches)) {
      return '';
    }

    return $matches[1];
  }

  /**
   * Parses the synopsis out of the doc comment
   *
   * @return string
   */
  public function getSynopsis() {
    if (!preg_match('|^@synopsis\s+(.+)|m', $this->docComment, $matches)) {
      return '';
    }

    return $matches[1];
  }

  /**
   * Parses tag of given name out of the doc comment
   *
   * @param string $name Name of the tag to retrieve
   * @return string
   */
  public function getTag($name) {
    if (preg_match(
      '|^@' . $name . '\s+([a-z-_]+)|m',
      $this->docComment,
      $matches
    )) {
      return $matches[1];
    }

    return '';
  }

  /**
   * Removes decorators from the given string
   *
   * @param string $comment Will be undecorated
   * @return string
   */
  private function removeDecorations($comment) {
    $comment = preg_replace('|^/\*\*[\r\n]+|', '', $comment);
    $comment = preg_replace('|\n[\t ]*\*/$|', '', $comment);
    $comment = preg_replace('|^[\t ]*\* ?|m', '', $comment);

    return $comment;
  }

}
