<?php

namespace Pantheon\Terminus\Tests\Unit\PublicApi;

use GuzzleHttp\Psr7\Response;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Exceptions\TerminusNotFoundException;
use Pantheon\Terminus\PublicApi\SiteEnvResolver;
use PHPUnit\Framework\TestCase;

class SiteEnvResolverTest extends TestCase
{
    public const SITE_ID = '7d6c59e4-304a-41e9-97ab-f348eedc3996';

    public static function environments(array $overrides = []): array
    {
        $edges = [];
        foreach (['dev', 'test', 'live'] as $id) {
            $edges[] = ['cursor' => $id, 'node' => ($overrides[$id] ?? []) + [
                'id' => $id,
                'name' => $id,
                'isInitialized' => true,
                'isLocked' => false,
                'username' => null,
                'password' => null,
            ]];
        }
        return [
            'edges' => $edges,
            'pageInfo' => ['hasNextPage' => false, 'hasPreviousPage' => false, 'endCursor' => 'live'],
            'totalCount' => 3,
        ];
    }

    public function testParse(): void
    {
        $this->assertSame(['my-site', 'dev'], SiteEnvResolver::parse('my-site.dev'));
        $this->assertSame(['my-site', 'live'], SiteEnvResolver::parse('my-site', 'live'));
        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('<site_name>.<environment>');
        SiteEnvResolver::parse('my-site');
    }

    public function testResolvesByName(): void
    {
        $h = new ClientTestHelper([
            new Response(200, [], json_encode(self::SITE_ID)),
            ClientTestHelper::json(200, self::environments()),
        ]);

        $siteEnv = (new SiteEnvResolver($h->client))->resolve('my-site.test');

        $this->assertSame(self::SITE_ID, $siteEnv->siteId);
        $this->assertSame('my-site', $siteEnv->siteName);
        $this->assertSame('test', $siteEnv->envName);
        $this->assertSame('test', $siteEnv->environment['id']);
        $this->assertSame([
            'GET /v1/sites/by-name/my-site',
            'GET /v1/sites/' . self::SITE_ID . '/environments',
        ], $h->sent());
    }

    public function testResolvesByUuidAndLooksUpName(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, ['id' => self::SITE_ID, 'machineName' => 'my-site']),
            ClientTestHelper::json(200, self::environments()),
        ]);

        $siteEnv = (new SiteEnvResolver($h->client))->resolve(strtoupper(self::SITE_ID) . '.dev');

        $this->assertSame(self::SITE_ID, $siteEnv->siteId);
        $this->assertSame('my-site', $siteEnv->siteName);
        $this->assertSame([
            'GET /v1/sites/' . self::SITE_ID,
            'GET /v1/sites/' . self::SITE_ID . '/environments',
        ], $h->sent());
    }

    public function testUnknownSite(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(403, ['errors' => [[
            'message' => 'You do not have access to this resource, or it does not exist.',
            'extensions' => ['code' => 'UNAUTHORIZED'],
        ]]])]);

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('Could not locate a site your user may access identified by nope');
        (new SiteEnvResolver($h->client))->resolve('nope.dev');
    }

    public function testUnknownEnvironment(): void
    {
        $h = new ClientTestHelper([
            new Response(200, [], json_encode(self::SITE_ID)),
            ClientTestHelper::json(200, self::environments()),
        ]);

        $this->expectException(TerminusNotFoundException::class);
        $this->expectExceptionMessage('Could not find an environment identified by mdev.');
        (new SiteEnvResolver($h->client))->resolve('my-site.mdev');
    }
}
