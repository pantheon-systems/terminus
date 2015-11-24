<?php

use Terminus\Outputters\BashFormatter;
use Terminus\Outputters\JSONFormatter;
use Terminus\Outputters\PrettyFormatter;
use Terminus\Outputters\StreamWriter;

/**
 * Class TestOutputters
 */
class TestOutputters extends PHPUnit_Framework_TestCase {

  protected $values;
  protected $records;
  protected $recordLabels;

  public function setUp() {
    $this->values = array(
      'Integer'      => 1234,
      'String'       => 'abc',
      'Human String' => 'Hello, World!',
      'Nothing'      => null,
      'Array'        => array('foo', 'bar', 'baz')
    );

    $this->records = array(
      (object)array(
        'foo' => 'abc',
        'bar' => 123,
        'baz' => 'extra'
      ),
      (object)array(
        'foo'      => 'def',
        'bar'      => 456,
        'unlabled' => 'abc',
      ),
      (object)array(
        'foo' => 'ghi',
        'bar' => 678,
        'biz' => 'another extra'
      )
    );

    $this->recordLabels = array(
      'foo' => 'Foo',
      'bar' => 'Bar',
      'baz' => 'Extra 1',
      'biz' => 'Extra 2'
    );
  }

  /**
   * @covers: Terminus\Outputters\JSONFormatter
   */
  public function testJsonFormatter() {
    $formatter = new JSONFormatter();

    foreach ($this->values as $label => $value) {
      $formatted = $formatter->formatValue($value, $label);
      $this->assertEquals(json_encode($value), $formatted);
      $this->assertEquals($value, json_decode($formatted));
      $this->assertEquals(JSON_ERROR_NONE, json_last_error());
      // Make sure the human label is ignored.
      $this->assertNotContains($label, $formatted);
    }

    foreach ($this->records as $label => $value) {
      $formatted = $formatter->formatRecord($value, $this->recordLabels);
      $this->assertEquals(json_encode($value), $formatted);
      $this->assertEquals($value, json_decode($formatted));
      $this->assertEquals(JSON_ERROR_NONE, json_last_error());
    }

    $formatted = $formatter->formatValueList($this->values, array_keys($this->values));
    $this->assertEquals(json_encode($this->values), $formatted);
    $this->assertEquals($this->values, json_decode($formatted, true));
    $this->assertEquals(JSON_ERROR_NONE, json_last_error());

    $formatted = $formatter->formatRecordList($this->records, $this->recordLabels);
    $this->assertEquals(json_encode($this->records), $formatted);
    $this->assertEquals($this->records, json_decode($formatted));
    $this->assertEquals(JSON_ERROR_NONE, json_last_error());
  }

  /**
   * @covers: \Terminus\Outputters\JSONFormatter
   */
  public function testPrettyFormatter() {
    $formatter = new PrettyFormatter();

    foreach ($this->values as $label => $value) {
      $formatted = $formatter->formatValue($value, $label);
      // Make sure the human label is there.
      $this->assertContains($label, $formatted);
      if ($value) {
        foreach ((array)$value as $val) {
          $this->assertContains((string)$val, $formatted);
        }
      }
    }

    // @TODO: This cannot be tested because we're using an output buffer to generate the tables.
    foreach ($this->records as $value) {
      $formatted = $formatter->formatRecord($value, $this->recordLabels);
    }
  }

  /**
   * @covers: \Terminus\Outputters\JSONFormatter
   */
  public function testBashFormatter() {
    $formatter = new BashFormatter();

    foreach ($this->values as $label => $value) {
      $formatted = $formatter->formatValue($value, $label);
      if ($value) {
        foreach ((array)$value as $val) {
          $this->assertContains((string)$val, $formatted);
        }
      }
      // Make sure the human label is ignored.
      $this->assertNotContains($label, $formatted);
    }

    // @TODO: This cannot be tested because we're using an output buffer to generate the tables.
    foreach ($this->records as $value) {
      $formatted = $formatter->formatRecord($value, $this->recordLabels);
      foreach ((array)$value as $field => $val) {
        $this->assertContains((string)$val, $formatted);
        $this->assertContains($field, $formatted);
      }
      foreach ($this->recordLabels as $label) {
        // Make sure the human label is there.
        $this->assertNotContains($label, $formatted);
      }
    }

    $formatted = $formatter->formatRecordList($this->records, $this->recordLabels);
    foreach ($this->records as $value) {
      foreach ((array)$value as $field => $val) {
        $this->assertContains((string)$val, $formatted);
      }
    }
    foreach ($this->recordLabels as $label) {
      // Make sure the human label is there.
      $this->assertNotContains($label, $formatted);
    }
  }

}
