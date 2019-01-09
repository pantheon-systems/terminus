<?php

namespace Pantheon\Terminus\UnitTests\Hooks;

use Consolidation\AnnotatedCommand\AnnotationData;
use Pantheon\Terminus\Collections\Sites;
use Pantheon\Terminus\Config\TerminusConfig;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Hooks\SiteEnvLookup;
use Pantheon\Terminus\Models\Site;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SiteEnvLookupTest
 * Testing class for Pantheon\Terminus\Hooks\SiteEnvLookup
 * @package Pantheon\Terminus\UnitTests\Hooks
 */
class SiteEnvLookupTest extends \PHPUnit_Framework_TestCase
{
    const SITE_ID_FIXTURE = 'abc';

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

    // An input definition that takes a site_env and a variable list of arguments
    protected function siteEnvVarArgsDef()
    {
        $commandArg = new \Symfony\Component\Console\Input\InputArgument('command');
        $siteEnvArg = new \Symfony\Component\Console\Input\InputArgument('site_env');
        $arrayArg = new \Symfony\Component\Console\Input\InputArgument('list', \Symfony\Component\Console\Input\InputArgument::IS_ARRAY);

        return new \Symfony\Component\Console\Input\InputDefinition([$commandArg, $siteEnvArg, $arrayArg]);
    }

    // An input definition that takes a site_env and a single additional argument
    protected function siteEnvRequiredArgsDef()
    {
        $commandArg = new \Symfony\Component\Console\Input\InputArgument('command');
        $siteEnvArg = new \Symfony\Component\Console\Input\InputArgument('site_env');
        $singleRequiredArg = new \Symfony\Component\Console\Input\InputArgument('item');

        return  new \Symfony\Component\Console\Input\InputDefinition([$commandArg, $siteEnvArg, $singleRequiredArg]);
    }

    // An input definition that takes a site and a single additional argument
    protected function siteRequiredArgsDef()
    {
        $commandArg = new \Symfony\Component\Console\Input\InputArgument('command');
        $siteArg = new \Symfony\Component\Console\Input\InputArgument('site');
        $singleRequiredArg = new \Symfony\Component\Console\Input\InputArgument('item');

        return  new \Symfony\Component\Console\Input\InputDefinition([$commandArg, $siteArg, $singleRequiredArg]);
    }

    // Configuration values that would be set via TERMINUS_SITE / TERMINUS_ENV
    // or a .env file.
    protected function terminusSiteWithTerminusEnv()
    {
        return [
            'site' => 'site-from-config',
            'env' => 'dev',
        ];
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->config = new TerminusConfig();

        $this->site = $this->getMockBuilder(Site::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->site->id = self::SITE_ID_FIXTURE;

        $this->site->method('getName')
            ->willReturn('site-from-repo');

        $this->sites = $this->getMockBuilder(Sites::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->sites->method('get')
            ->willReturn($this->site);

        $this->sites->expects($this->never())
            ->method('someMethod');

        $this->sites->expects($this->any())
            ->method('getSite')
            ->with(self::SITE_ID_FIXTURE)
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
        return [

            // Site not specified on commandline
            // Command takes site_env and variable arguments
            // TERMINUS_SITE and TERMINUS_ENV set in configuration
            [
                ['command: example:op', 'site_env: site-from-config.dev', 'list: [a, b]'],
                ['program', 'example:op', 'a', 'b'],
                $this->siteEnvVarArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Like the previous test, but a different site is specified on the commandline
            [
                ['command: example:op', 'site_env: othersite.test', 'list: [a, b]'],
                ['program', 'example:op', 'othersite.test', 'a', 'b'],
                $this->siteEnvVarArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Site not specified on commandline, and nothing provided in configuration
            [
                ['command: example:op', 'site_env: a', 'list: [b]'],
                ['program', 'example:op', 'a', 'b'],
                $this->siteEnvVarArgsDef(),
                [],
            ],

            // Site not speicifed on commandline
            // Command takes site_env and one other required argument
            // TERMINUS_SITE and TERMINUS_ENV set in configuration
            [
                ['command: example:op', 'site_env: site-from-config.dev', 'item: a'],
                ['program', 'example:op', 'a'],
                $this->siteEnvRequiredArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Like the previous test, but a different site is specified on the commandline
            [
                ['command: example:op', 'site_env: othersite.test', 'item: a'],
                ['program', 'example:op', 'othersite.test', 'a'],
                $this->siteEnvRequiredArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Site not specified on commandline, and nothing provided in configuration
            [
                ['command: example:op', 'site_env: a', 'item: EMPTY'],
                ['program', 'example:op', 'a'],
                $this->siteEnvRequiredArgsDef(),
                [],
            ],

            // Site not speicifed on commandline
            // Command takes site and one other required argument
            // TERMINUS_SITE and TERMINUS_ENV set in configuration
            [
                ['command: example:op', 'site: site-from-config', 'item: a'],
                ['program', 'example:op', 'a'],
                $this->siteRequiredArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Like the previous test, but a different site is specified on the commandline
            [
                ['command: example:op', 'site: othersite', 'item: a'],
                ['program', 'example:op', 'othersite', 'a'],
                $this->siteRequiredArgsDef(),
                $this->terminusSiteWithTerminusEnv(),
            ],

            // Site not specified on commandline, and nothing provided in configuration
            [
                ['command: example:op', 'site: EMPTY', 'item: a'],
                ['program', 'example:op', 'a'],
                $this->siteRequiredArgsDef(),
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

        $input = new \Symfony\Component\Console\Input\ArgvInput($args, $def);
        $annotationData = new AnnotationData();

        $this->siteEnvLookup->siteAndEnvLookupHook($input, $annotationData);

        $expectedString = implode("\n", $expected);
        $actualArgs = $input->getArguments();

        // Convert from associative key => value to list "key: value"
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

        $actualString = implode("\n", $actualArgs);

        $this->assertEquals($expectedString, $actualString);
    }

    /**
     * This test feeds more data to siteEnvLookupParameters
     * after setting up a git repository fixture to test detecting
     * a site via the git repository information.
     */
    public function testInjectSiteFromRepoUrlLookup()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'terminus_test_');
        unlink($tmp);
        mkdir($tmp);

        // Set up a fixture repository simulating a Pantheon
        // site with a site id of "abc", which lines up with
        // the site id recognized by the mocked site 'site-from-repo'.
        $site_id = self::SITE_ID_FIXTURE;
        passthru("git -C $tmp init");
        passthru("git -C $tmp config user.email 'ci@example.com'");
        passthru("git -C $tmp config user.name 'CI Bot'");
        passthru("git -C $tmp remote add origin 'ssh://codeserver.dev.{$site_id}@codeserver.dev.${site_id}.drush.in:2222/~/repository.git'");
        file_put_contents("$tmp/file", 'placeholder');
        passthru("git -C $tmp add file");
        passthru("git -C $tmp commit -m Testing");

        chdir($tmp);

        $this->testEnsureSiteEnvInjected(
            ['command: example:op', 'site_env: site-from-repo.dev', 'item: a'],
            ['program', 'example:op', 'a'],
            $this->siteEnvRequiredArgsDef(),
            []
        );

        // Change our fixture repository to simulate a repository
        // that is not a Pantheon site.
        passthru("git -C $tmp remote set-url origin 'git@github.com:org/project.git'");

        $this->testEnsureSiteEnvInjected(
            ['command: example:op', 'site_env: a', 'item: EMPTY'],
            ['program', 'example:op', 'a'],
            $this->siteEnvRequiredArgsDef(),
            []
        );

        // For some reason, Appveyor fails with "permission denied" when
        // trying to remove objects in the .git directory. We'll just let
        // Appveyor stay dirty.
        if (getenv('APPVEYOR')) {
            return;
        }

        // Recursively remove tmp directory
        $fs = new Filesystem();
        $fs->remove($tmp);
    }
}
