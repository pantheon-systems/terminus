<?php

namespace Terminus\Helpers;

/**
 * Class TemplateHelper
 * Render PHP or other types of files using Twig templates
 *
 * @package Terminus\Helpers
 */
class TemplateHelper extends TerminusHelper {

  /**
   * @var string
   */
  private $template_root;

  /**
   * TemplateHelper constructor.
   *
   * @param array $options Options and dependencies for this helper
   * @return TerminusHelper $this
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
    $this->template_root = TERMINUS_ROOT . '/templates';
  }

  /**
   * Renders the data using the given options.
   *
   * @param array $options Elements as follow:
   *  string template_name File name of the template to be used
   *  array  data          Context to pass through for template use
   *  array  options       Options to pass through for template use
   * @return string The rendered template
   */
  public function render(array $options = []) {
    $loader   = new \Twig_Loader_Filesystem($this->template_root);
    $twig     = new \Twig_Environment($loader);
    $rendered = $twig->render($options['template_name'], $options);
    return $rendered;
  }

}
