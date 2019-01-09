<?php

namespace Pantheon\Terminus\UnitTests\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Hooks\SiteEnvLookup;
use Pantheon\Terminus\Models\Site;

/**
 * Class SiteEnvLookupTest
 * Testing class for Pantheon\Terminus\Hooks\SiteEnvLookup
 * @package Pantheon\Terminus\UnitTests\Hooks
 */
class SiteEnvLookupTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SiteEnvLookup
     */
    protected $siteEnvLookup;
    /**
     * @var TerminusConfig
     */
    protected $config;
    /**
     * @var AnnotationData
     */
    protected $annotationData;
    /**
     * @var Site
     */
    protected $site;
    /**
     * @var Sites
     */
    protected $sites;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = new TerminusConfig();

        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = 'abc';

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->method('get')
            ->willReturn($this->site);


        $this->siteEnvLookup = new SiteEnvLookup();
        $this->siteEnvLookup->setConfig($this->config);
        $this->siteEnvLookup->setSites($this->sites);
    }

    /**
     * Data provider for testEnsureSiteEnvInjected
     */
    public function siteEnvLookupParameters()
    {
        // Some argument definitions that we will use to build some input definitions
        $commandArg = new \Symfony\Component\Console\Input\InputArgument('command');
        $siteEnvArg = new \Symfony\Component\Console\Input\InputArgument('site_env');
        $siteArg = new \Symfony\Component\Console\Input\InputArgument('site');
        $arrayArg = new \Symfony\Component\Console\Input\InputArgument('list', \Symfony\Component\Console\Input\InputArgument::IS_ARRAY);
        $singleRequiredArg = new \Symfony\Component\Console\Input\InputArgument('item');

        // An input definition that takes a site_env and a variable list of arguments
        $siteEnvVarArgsDef = new \Symfony\Component\Console\Input\InputDefinition([$commandArg, $siteEnvArg, $arrayArg]);

        // An input definition that takes a site and a single additional argument
        $siteRequiredArgsDef = new \Symfony\Component\Console\Input\InputDefinition([$commandArg, $siteEnvArg, $singleRequiredArg]);

        // Some configuration values
        $terminusSiteWithTerminusEnv = [
            'site' => 'mysite',
            'env' => 'dev',
        ];

        return [

            // Site not specified on commandline
            // Command takes site_env and variable arguments
            // TERMINUS_SITE and TERMINUS_ENV set in configuration
            [
                ['command: example:op', 'site_env: mysite.dev', 'list: [a, b]'],
                ['program', 'example:op', 'a', 'b'],
                $siteEnvVarArgsDef,
                $terminusSiteWithTerminusEnv,
            ],

            // Like the previous test, but a different site is specified on the commandline
            [
                ['command: example:op', 'site_env: othersite.test', 'list: [a, b]'],
                ['program', 'example:op', 'othersite.test', 'a', 'b'],
                $siteEnvVarArgsDef,
                $terminusSiteWithTerminusEnv,
            ],

            // Site not specified on commandline, and nothing provided in configuration
            [
                ['command: example:op', 'site_env: a', 'list: [b]'],
                ['program', 'example:op', 'a', 'b'],
                $siteEnvVarArgsDef,
                [],
            ],

            // Site not speicifed on commandline
            // Command takes site_env and one other required argument
            // TERMINUS_SITE and TERMINUS_ENV set in configuration
            [
                ['command: example:op', 'site_env: mysite.dev', 'item: a'],
                ['program', 'example:op', 'a'],
                $siteRequiredArgsDef,
                $terminusSiteWithTerminusEnv,
            ],

            // Like the previous test, but a different site is specified on the commandline
            [
                ['command: example:op', 'site_env: othersite.test', 'item: a'],
                ['program', 'example:op', 'othersite.test', 'a'],
                $siteRequiredArgsDef,
                $terminusSiteWithTerminusEnv,
            ],

            // Site not specified on commandline, and nothing provided in configuration
            [
                ['command: example:op', 'site_env: a', 'item: EMPTY'],
                ['program', 'example:op', 'a'],
                $siteRequiredArgsDef,
                [],
            ],

        ];
    }

    /**
     * Tests the SiteEnvLookupTest::siteAndEnvLookupHook when
     * the command has a 'site_env' parameter, and TERMINUS_SITE
     * and TERMINUS_ENV are specified in configuration.
     *
     * @dataProvider siteEnvLookupParameters
     */
    public function testEnsureSiteEnvInjected(array $expected, array $args, \Symfony\Component\Console\Input\InputDefinition $def, array $configData)
    {
        $this->config->replace($configData);

        $this->sites->expects($this->never())
            ->method('getSite');

        $input = new \Symfony\Component\Console\Input\ArgvInput($args, $def);
        $annotationData = new AnnotationData();

/*
        $this->sites->expects($this->once())
            ->method('getSite')
            ->with('abc')
            ->willReturn($this->site);
*/

        $this->siteEnvLookup->siteAndEnvLookupHook($input, $annotationData);

        $expectedString = implode("\n", $expected);
        $actualArgs = $input->getArguments();

        $actualArgs = array_map(
            function ($key) use ($actualArgs) {
                $value = $actualArgs[$key];
                if (is_array($value)) {
                    $value = '[' . implode(', ', $value) . ']';
                } elseif (empty($value)) {
                    $value = 'EMPTY';
                }
                return "$key: $value";
            },
            array_keys($actualArgs)
        );

        //$actualString = var_export($input->getArguments(), true); // implode("\n", $input->getArguments());
        $actualString = implode("\n", $actualArgs);

        $this->assertEquals($expectedString, $actualString);
    }
}
