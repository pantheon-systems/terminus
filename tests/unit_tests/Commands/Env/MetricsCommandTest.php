<?php
namespace Pantheon\Terminus\UnitTests\Commands\Env;

use Pantheon\Terminus\Commands\Env\MetricsCommand;
use Pantheon\Terminus\Collections\EnvironmentMetrics;
use Pantheon\Terminus\Models\Metric;

/**
 * Class MetricsCommandTest
 * Testing class for Pantheon\Terminus\Commands\Env\MetricsCommand
 * @package Pantheon\Terminus\UnitTests\Commands\Env
 */
class MetricsCommandTest extends EnvCommandTest
{
    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->command = new MetricsCommand($this->getConfig());
        $this->command->setLogger($this->logger);
        $this->command->setSites($this->sites);

        $this->metrics = $this->getMockBuilder(EnvironmentMetrics::class)
            ->disableOriginalConstructor()
            ->getMock();

        // Ignore the calls to the fluid initializers in the EnvironmentMetrics class.
        $this->metrics->method('setSeriesId')->willReturn($this->metrics);
        $this->metrics->method('setPeriod')->willReturn($this->metrics);
        $this->metrics->method('setDatapoints')->willReturn($this->metrics);
        $this->metrics->method('selectDatapoints')->willReturn(2);

        $this->environment->method('getEnvironmentMetrics')->willReturn($this->metrics);
        $this->environment->method('getName')->willReturn('live');

        $this->metric_1_attribs = [
            'id' => '1517443200',
            'datetime' => '2018-02-01T00:00:00',
            'value' => '1197',
        ];
        $this->metric_1 = new Metric((object)$this->metric_1_attribs);
        $this->metric_2_attribs = [
            'id' => '1519862400',
            'datetime' => '2018-03-01T00:00:00',
            'value' => '5111',
        ];
        $this->metric_2 = new Metric((object)$this->metric_2_attribs);
    }

    /**
     * Tests the env:metrics command success with all parameters
     */
    public function testLog()
    {
        $data = [
            'timeseries' => [
                '1517443200' => [
                    'datetime' => '2018-02-01T00:00:00',
                    'value' => '1197',
                ],
                '1519862400' => [
                    'datetime' => '2018-03-01T00:00:0',
                    'value' => '1197',
                ],
            ],
            'summary' => null,
        ];
        $this->environment->id = 'live';
        $this->metrics->method('serialize')
            ->willReturn($data);

        $out = $this->command->metrics('mysite.live');

        $this->assertInstanceOf('Consolidation\OutputFormatters\StructuredData\RowsOfFields', $out);
        $this->assertEquals($data, $out->getArrayCopy());
    }
}
