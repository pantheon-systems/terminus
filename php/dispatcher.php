<?php

namespace Terminus\Dispatcher;

/**
 * Get array of commands in object
 *
 * @param \Terminus\Dispatcher\RootCommand $command Chained command object
 * @return array Represents names of all commands in param
 */
function getPath($command) {
  $path = array();

  do {
    array_unshift($path, $command->getName());
  } while ($command = $command->getParent());

  return $path;
}
