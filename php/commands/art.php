<?php

/**
 * Print the pantheon art
 *
 */
class Art_Command extends Terminus_Command {

  private $works = array(
    "fist" => "CiAgICAgICAgLisuCiAgICAgICAgLis/OgogICAgICAgICAuKz8/LgogICAgICAgICAgID8/PyAuCiAgICAgICAgICAgKz8/Py4KICAgICAgKz8/Pz8/Pz8/Pz0uCiAgICAgIC4/Pz8/Pz8/Pz8/Py4KICAgICAgLj8/Pz8/Pz8/Pz8/Py4KCiAgICAgIyMjIyMjIyMjIyMgIyMjIyMjIyMKICAgICAjIyMjIyMjIyMjIyMuIyMjIyMjIy4KICAgICAjIyMjIyMjICMjIyMgIC4uLi4uLi4KICAgICAjIyMjIyMjIyAjIyMjICMjIyMjIyMgICAgICAgICAgICAgICAgNTAgNDEgNEUgNTQgNDggNDUgNEYgNEUKICAgICAjIyMjIyMjIyMuIyMjIy4jIyMjIyMgICAgICAgIF9fX19fX19fX19fX18gIF9fICBfX19fX19fXyAgX19fXyAgX19fX19fCiAgICAgIyMjIyMjICAuLi4gICAgICAgICAgICAgICAgIC9fICBfXy8gX18vIF8gXC8gIHwvICAvICBfLyB8LyAvIC8gLyAvIF9fLwogICAgICMjIyMjIyMuPz8uIyMjIyMjIyMjIyAgICAgICAgLyAvIC8gXy8vICwgXy8gL3xfLyAvLyAvLyAgICAvIC9fLyAvXCBcCiAgICAgIyMjIyMjI34rPz8uIyMjIyMjIyMjICAgICAgIC9fLyAvX19fL18vfF8vXy8gIC9fL19fXy9fL3xfL1xfX19fL19fXy8KICAgICAjIyMjIyMjIy4/Py4uCiAgICAgIyMjIyMjIyMjLj8/LiMjIyMjIyMuCiAgICAgIyMjIyMjIyMjLis/PyAjIyMjIyMuCiAgICAgICAgICAgICAgIC4rPy4KICAgICAgICAgLj8/Pz8/Pz8/Pz8/Py4KICAgICAgICAgICArPz8/Pz8/Pz8/PywKICAgICAgICAgICAgLj8/Pz8rKysrKysuCiAgICAgICAgICAgICAgPz8/Py4KICAgICAgICAgICAgICAuPz8/LAogICAgICAgICAgICAgICAufj8/LgogICAgICAgICAgICAgICAgIC4/PwogICAgICAgICAgICAgICAgICAuPywu",
    "unicorn" => "ICAgICAgIFwKICAgICAgICBcCiAgICAgICAgIFxcCiAgICAgICAgICBcXAogICAgICAgICAgID5cLzcKICAgICAgIF8uLSg2JyAgXAogICAgICAoPV9fXy5fL2AgXAogICAgICAgICAgICkgIFwgfAogICAgICAgICAgLyAgIC8gfAogICAgICAgICAvICAgID4gLwogICAgICAgIGogICAgPCBfXAogICAgXy4tJyA6ICAgICAgYGAuCiAgICBcIHI9Ll9cICAgICAgICBgLgogICA8YFxcXyAgXCAgICAgICAgIC5gLS4KICAgIFwgci03ICBgLS4gLl8gICcgLiAgYFwKICAgICBcYCwgICAgICBgLS5gNyAgNykgICApCiAgICAgIFwvICAgICAgICAgXHwgIFwnICAvIGAtLl8KICAgICAgICAgICAgICAgICB8fCAgICAuJwogICAgICAgICAgICAgICAgICBcXCAgKAogICAgICAgICAgICAgICAgICAgPlwgID4KICAgICAgICAgICAgICAgLC4tJyA+LicKICAgICAgICAgICAgICA8LidfLicnCiAgICAgICAgICAgICAgICA8Jw=="
  );

  /**
   * View Pantheon artwork
   *
   */
  function __invoke( $args, $assoc_args ) {
    $artwork = array_shift($args) ?: array_keys($this->works, 0);

    if (!empty($artwork) && array_key_exists($artwork, $this->works)){
      echo Terminus::colorize("%g".base64_decode($this->works[$artwork])."%n")."\n";
    } else {
      Terminus::error("No formula for requested artwork");
    }
  }

}

Terminus::add_command( 'art', 'Art_Command' );
