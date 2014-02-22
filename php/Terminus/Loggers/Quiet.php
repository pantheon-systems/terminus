<?php

namespace Terminus\Loggers;

class Quiet {

	function info( $message ) {
		// nothing
	}

	function success( $message ) {
		// nothing
	}

	function warning( $message ) {
		// nothing
	}

	function error( $message ) {
		fwrite( STDERR, \Terminus::colorize( "%RError:%n $message\n" ) );
	}
}

