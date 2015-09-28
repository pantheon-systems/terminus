<?php

namespace Terminus\Dispatcher;
use Terminus\Exceptions\TerminusException;

/**
 * A leaf node in the command tree.
 */
class Subcommand extends CompositeCommand {

  private $alias;

  private $when_invoked;

  function __construct( $parent, $name, $docparser, $when_invoked ) {
    parent::__construct( $parent, $name, $docparser );

    $this->when_invoked = $when_invoked;

    $this->alias = $docparser->get_tag( 'alias' );

    $this->synopsis = $docparser->get_synopsis();
    if ( !$this->synopsis && $this->longdesc ) {
      $this->synopsis = self::extract_synopsis( $this->longdesc );
    }
  }

  private static function extract_synopsis( $longdesc ) {
    preg_match_all( '/(.+?)[\r\n]+:/', $longdesc, $matches );
    return implode( ' ', $matches[1] );
  }

  function can_have_subcommands() {
    return false;
  }

  function get_synopsis() {
    return $this->synopsis;
  }

  function get_alias() {
    return $this->alias;
  }

  function show_usage( $prefix = 'usage: ' ) {
    Terminus::line( $this->get_usage( $prefix ) );
  }

  function get_usage( $prefix ) {
    return sprintf( "%s%s %s",
      $prefix,
      implode( ' ', get_path( $this ) ),
      $this->get_synopsis()
    );
  }

  private function prompt( $question, $default ) {

    try {
      $response = \cli\prompt( $question, $default );
    } catch( \Exception $e ) {
      \Terminus::line();
      return false;
    }

    return $response;
  }

  private function prompt_args( $args, $assoc_args ) {

    $synopsis = $this->get_synopsis();

    if ( ! $synopsis )
      return array( $args, $assoc_args );

    $spec = array_filter( \Terminus\SynopsisParser::parse( $synopsis ), function( $spec_arg ) {
      return in_array( $spec_arg['type'], array( 'generic', 'positional', 'assoc', 'flag' ) );
    });

    $spec = array_values( $spec );

    // 'positional' arguments are positional (aka zero-indexed)
    // so $args needs to be reset before prompting for new arguments
    $args = array();
    foreach( $spec as $key => $spec_arg ) {

      $current_prompt = ( $key + 1 ) . '/' . count( $spec ) . ' ';
      $default = ( $spec_arg['optional'] ) ? '' : false;

      // 'generic' permits arbitrary key=value (e.g. [--<field>=<value>] )
      if ( 'generic' == $spec_arg['type'] ) {

        list( $key_token, $value_token ) = explode( '=', $spec_arg['token'] );

        $repeat = false;
        do {
          if ( ! $repeat )
            $key_prompt = $current_prompt . $key_token;
          else
            $key_prompt = str_repeat( " ", strlen( $current_prompt ) ) . $key_token;

          $key = $this->prompt( $key_prompt, $default );
          if ( false === $key )
            return array( $args, $assoc_args );

          if ( $key ) {
            $key_prompt_count = strlen( $key_prompt ) - strlen( $value_token ) - 1;
            $value_prompt = str_repeat( " ", $key_prompt_count ) . '=' . $value_token;

            $value = $this->prompt( $value_prompt, $default );
            if ( false === $value )
              return array( $args, $assoc_args );

            $assoc_args[$key] = $value;

            $repeat = true;
            $required = false;
          } else {
            $repeat = false;
          }

        } while( $required || $repeat );

      } else {

        $prompt = $current_prompt . $spec_arg['token'];
        if ( 'flag' == $spec_arg['type'] )
          $prompt .= ' (Y/n)';

        $response = $this->prompt( $prompt, $default );
        if ( false === $response )
          return array( $args, $assoc_args );

        if ( $response ) {
          switch ( $spec_arg['type'] ) {
            case 'positional':
              if ( $spec_arg['repeating'] )
                $response = explode( ' ', $response );
              else
                $response = array( $response );
              $args = array_merge( $args, $response );
              break;
            case 'assoc':
              $assoc_args[$spec_arg['name']] = $response;
              break;
            case 'flag':
              if ( 'Y' == $response )
                $assoc_args[$spec_arg['name']] = true;
              break;
          }
        }
      }
    }

    return array( $args, $assoc_args );
  }

  /**
   * @return array list of invalid $assoc_args keys to unset
   */
  private function validate_args( $args, $assoc_args, $extra_args ) {
    $synopsis = $this->get_synopsis();
    if ( !$synopsis )
      return array();

    $validator = new \Terminus\SynopsisValidator( $synopsis );

    $cmd_path = implode( ' ', get_path( $this ) );
    foreach ( $validator->get_unknown() as $token ) {
      \Terminus::log('warning',
        "The `{cmd}` command has an invalid synopsis part: {token}",
        array('cmd' => $cmd_path, 'token' => $token)
      );
    }

    if ( !$validator->enough_positionals( $args ) ) {
      $this->show_usage();
      exit(1);
    }

    if ( $this->name != 'help') {
      $invalid = $validator->invalid_positionals($args);
      if($invalid) {
        throw new TerminusException("Invalid positional value: {invalid}", array('invalid' => $invalid));
      }
    }

    $unknown_positionals = $validator->unknown_positionals($args);
    if ( !empty( $unknown_positionals ) ) {
      throw new TerminusException('Too many positional arguments: {args}',
        array('args' => implode( ' ', $unknown_positionals )));
    }

    list( $errors, $to_unset ) = $validator->validate_assoc(
      array_merge( \Terminus::get_config(), $extra_args, $assoc_args )
    );

    foreach ( $validator->unknown_assoc( $assoc_args ) as $key ) {
      $errors['fatal'][] = "unknown --$key parameter";
    }

    if ( !empty( $errors['fatal'] ) ) {
      $out = 'Parameter errors:';
      foreach ( $errors['fatal'] as $error ) {
        $out .= "\n " . $error;
      }

      throw new TerminusException($out);
    }

    foreach ($errors['warning'] as $warning) {
      \Terminus::log('warning', $warning);
    }

    return $to_unset;
  }

  function invoke( $args, $assoc_args, $extra_args ) {
    if ( \Terminus::get_config( 'interactive' ) )
      list( $args, $assoc_args ) = $this->prompt_args( $args, $assoc_args );

    $to_unset = $this->validate_args( $args, $assoc_args, $extra_args );

    foreach ( $to_unset as $key ) {
      unset( $assoc_args[ $key ] );
    }

    $path = get_path( $this->get_parent() );

    call_user_func( $this->when_invoked, $args, array_merge( $extra_args, $assoc_args ) );
  }
}
