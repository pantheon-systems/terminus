<?php

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\TemplateHelper;
use Terminus\Runner;

/**
 * Testing class for Terminus\Helpers\TemplateHelper
 */
class TemplateHelperTest extends PHPUnit_Framework_TestCase {

  /**
   * @var TemplateHelper
   */
  private $template_helper;

  public function __construct() {
    $command               = new ArtCommand(['runner' => new Runner()]);
    $this->template_helper = new TemplateHelper(compact('command'));
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