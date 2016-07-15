<?php

namespace Terminus\Commands;

use Terminus\Models\Collections\Upstreams;

/**
 * Show Pantheon upstream information
 *
 * @command upstreams
 */
class UpstreamsCommand extends TerminusCommand {

  /**
   * Search for Pantheon upstream info
   *
   * ### OPTIONS
   *
   * [--category=<category>]
   * : general, publishing, commerce, etc
   *
   * [--type=<type>]
   * : Pantheon internal upstream type definition
   *
   * [--framework=<drupal|wordpress>]
   * : Filter based on framework
   *
   * @subcommand list
   * @alias all
   */
  public function all(array $args = [], array $assoc_args = []) {
    $upstreams      = new Upstreams();
    $upstreams_list = $upstreams->fetch()->filter($assoc_args)->list(
      'id',
      ['id', 'longname', 'category', 'type', 'framework',]
    );
    $this->output()->outputRecordList(
      $upstreams_list,
      [
        'id'        => 'ID',
        'longname'  => 'Name',
        'category'  => 'Category',
        'type'      => 'Type',
        'framework' => 'Framework',
      ]
    );
    return $upstreams;
  }

}

