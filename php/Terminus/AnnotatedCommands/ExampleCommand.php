<?php

namespace Terminus\AnnotatedCommands;

use Robo\ResultData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputAwareInterface;
use Robo\Contract\OutputAwareInterface;
use Robo\Common\OutputAwareTrait;
use Robo\Common\InputAwareTrait;

// TODO: add input and output aware interfaces
class ExampleCommand implements InputAwareInterface, OutputAwareInterface
{
    use InputAwareTrait;
    use OutputAwareTrait;

    /**
     * @command example-table
     *
     * Demonstrate Robo formatters.  Default format is 'table'.
     *
     * @field-labels
     *   first: I
     *   second: II
     *   third: III
     * @usage try:formatters --format=yaml
     * @usage try:formatters --format=csv
     * @usage try:formatters --fields=first,third
     * @usage try:formatters --fields=III,II
     */
  public function exampleTable($options = ['format' => 'table', 'fields' => '']) {

    $outputData = [
        [ 'first' => 'One',  'second' => 'Two',  'third' => 'Three' ],
        [ 'first' => 'Eins', 'second' => 'Zwei', 'third' => 'Drei'  ],
        [ 'first' => 'Ichi', 'second' => 'Ni',   'third' => 'San'   ],
        [ 'first' => 'Uno',  'second' => 'Dos',  'third' => 'Tres'  ],
    ];
    // Note that we can also simply return the output data array here.
    return ResultData::message(new RowsOfFields($outputData));
  }

    /**
     * Demonstrate direct use of an output object via OutputAwareTrait
     */
  public function hello($who) {

    $this->output()->writeln("Hello, $who");
  }

}
