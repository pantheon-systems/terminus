<?php

use Behat\Gherkin\Node\PyStringNode,
    Behat\Gherkin\Node\TableNode;

$steps->Given( '/^an empty directory$/',
  function ( $world ) {
    $world->create_run_dir();
  }
);

$steps->Given( '/^an? ([^\s]+) file:$/',
  function ( $world, $path, PyStringNode $content ) {
    $content = (string) $content . "\n";
    $full_path = $world->variables['RUN_DIR'] . "/$path";
    Process::create( \TERMINUS\utils\esc_cmd( 'mkdir -p %s', dirname( $full_path ) ) )->run_check();
    file_put_contents( $full_path, $content );
  }
);

$steps->Given( '/^save (STDOUT|STDERR) ([\'].+[^\'])?as \{(\w+)\}$/',
  function ( $world, $stream, $output_filter, $key ) {

    if ( $output_filter ) {
      $output_filter = '/' . trim( str_replace( '%s', '(.+[^\b])', $output_filter ), "' " ) . '/';
      if ( false !== preg_match( $output_filter, $world->result->$stream, $matches ) )
        $output = array_pop( $matches );
      else
        $output = '';
    } else {
      $output = $world->result->$stream;
    }
    $world->variables[ $key ] = trim( $output, "\n" );
  }
);
