<?php

use Terminus\Helpers\TemplateHelper;

/**
 * Testing class for Terminus\Helpers\TemplateHelper
 */
class TemplateHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var TemplateHelper
   */
  private $template_helper;

  public function __construct() {

    $this->template_helper = new TemplateHelper(['logger' => getLogger()]);
  }

  public function testTwigRender() {
    $options = [
      'template_name' => 'man.twig',
      'data'          => [],
      'options'       => [],
    ];
    $rendered_template = $this->template_helper->render($options);
    $this->assertTrue(strpos($rendered_template, '##NAME') === 0);
  }

}