<?php
// are dev requirements installed?
if ( file_exists('vendor/bin/symfony-autocomplete') ) {
    $autocomplete = shell_exec('vendor/bin/symfony-autocomplete bin/terminus');
    // d($autocomplete);
    file_put_contents('assets/autocomplete.txt', $autocomplete);
} elseif ( strlen(shell_exec('which symfony-autocomplete')) > 0 ) {
    // global install?
    $autocomplete = shell_exec('symfony-autocomplete bin/terminus');
    file_put_contents('assets/autocomplete.txt', $autocomplete);
} else {
    echo "Please install dev dependencies, or run composer global require bamarni/symfony-console-autocomplete";
    exit(1);
}
