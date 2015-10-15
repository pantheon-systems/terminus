<?php

namespace Terminus\Dispatcher;

/**
 * Get array of commands in object
 *
 * @param [Command] $command Chained command object
 * @return [array] $path Represents names of all commands in param
 */
function getPath($command) {
  $path = array();

  do {
    array_unshift($path, $command->get_name());
  } while ($command = $command->get_parent());

  return $path;
}
