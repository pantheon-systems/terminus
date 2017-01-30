<?php

namespace Pantheon\Terminus\UnitTests\Config;

use Pantheon\Terminus\Config\TerminusConfig;

class TerminusConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $config;

    public function setUp()
    {
        parent::setUp();

        $this->config = new TerminusConfig();
    }

    public function testToArray()
    {
        $this->config->set('foo', 'bar');
        $this->config->set('abc', '123');
        $this->assertEquals(['foo' => 'bar', 'abc' => '123'], $this->config->toArray());
    }

    public function testFromArray()
    {
        $this->config->fromArray(['foo' => 'bar', 'abc' => '123']);
        $this->assertEquals('bar', $this->config->get('foo'));
        $this->assertEquals('123', $this->config->get('abc'));
    }

    public function testSet()
    {
        $this->config->set('TERMINUS_SOME_VAR', 'abc');
        $this->config->set('TERMINUS_ANOTHER_VAR', '123');
        $this->config->set('third_var', '321');
        $this->assertEquals('abc', $this->config->get('some_var'));
        $this->assertEquals('123', $this->config->get('another_var'));
        $this->assertEquals('321', $this->config->get('third_var'));
    }

    public function testGet()
    {
        $this->config->set('TERMINUS_SOME_VAR', 'abc');
        $this->config->set('TERMINUS_ANOTHER_VAR', '[[ TERMINUS_SOME_VAR ]]/123');
        $this->config->set('third_var', '[[ TERMINUS_ANOTHER_VAR ]]\321');
        $this->assertEquals('abc' . DIRECTORY_SEPARATOR . '123', $this->config->get('another_var'));
        $this->assertEquals('abc' . DIRECTORY_SEPARATOR . '123' . DIRECTORY_SEPARATOR . '321', $this->config->get('third_var'));
    }

    public function testKeys()
    {
        $this->config->fromArray(['foo' => 'bar', 'abc' => '123']);
        $this->assertEquals(['foo', 'abc'], $this->config->keys());
    }

    public function testExtend()
    {
        $a = new TerminusConfig();
        $b = new TerminusConfig();

        $a->setSourceName('Source A');
        $b->setSourceName('Source B');

        $a->fromArray(['foo' => 'bar', 'abc' => 123]);
        $b->fromArray(['baz' => 'bar', 'abc' => 321]);

        $a->extend($b);

        $this->assertEquals('bar', $a->get('foo'));
        $this->assertEquals('bar', $a->get('baz'));
        $this->assertEquals(321, $a->get('abc'));
        $this->assertEquals('Source A', $a->getSource('foo'));
        $this->assertEquals('Source B', $a->getSource('baz'));
        $this->assertEquals('Source B', $a->getSource('abc'));
    }
}
