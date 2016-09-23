<?php

namespace Terminus\UnitTests\Helpers;

use Terminus\Commands\ArtCommand;
use Terminus\Helpers\TemplateHelper;
use Terminus\UnitTests\TerminusTest;

/**
 * Testing class for Terminus\Helpers\TemplateHelper
 */
class TemplateHelperTest extends TerminusTest
{

  /**
   * @var TemplateHelper
   */
    private $template_helper;

    public function setUp()
    {
        parent::setUp();
        $command = new ArtCommand(['runner' => $this->runner,]);
        $this->template_helper = new TemplateHelper(compact('command'));
    }

    public function testTwigRender()
    {
        $options = [
        'template_name' => 'man.twig',
        'data'          => [],
        'options'       => [],
        ];
        $rendered_template = $this->template_helper->render($options);
        $this->assertTrue(strpos($rendered_template, '##NAME') === 0);
    }
}
