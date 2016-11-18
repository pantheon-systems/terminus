<?php

namespace Terminus\UnitTests;

use Pantheon\Terminus\Completions;

/**
 * Testing class for Terminus\Completions
 */
class CompletionsTest extends TerminusTest
{

  /**
   * @var string
   */
    private $command;

  /**
   * @var Completions
   */
    private $completions;

    public function setUp()
    {
        parent::setUp();
        $this->command     = 'terminus cli info';
        $this->completions = new Completions($this->command);
    }

    public function testConstruct()
    {
        $this->assertObjectHasAttribute('words', $this->completions);
        $this->assertObjectHasAttribute('options', $this->completions);
    }

    public function testGetOptions()
    {
        $options = $this->completions->getOptions();
        $this->assertTrue(in_array('--format=', $options));
    }
}
