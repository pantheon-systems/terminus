<?php

namespace Pantheon\Terminus\Tests\Unit\PublicApi;

use Pantheon\Terminus\PublicApi\Pager;
use Pantheon\Terminus\PublicApi\PublicApiException;
use PHPUnit\Framework\TestCase;

class PagerTest extends TestCase
{
    private static function page(array $ids, bool $hasNext, ?string $endCursor): array
    {
        return [
            'edges' => array_map(fn ($id) => ['cursor' => "c-$id", 'node' => ['id' => $id]], $ids),
            'pageInfo' => ['hasNextPage' => $hasNext, 'hasPreviousPage' => false, 'endCursor' => $endCursor],
        ];
    }

    public function testWalksAllPagesWithFirstAndAfter(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, self::page(['a', 'b'], true, 'c-b')),
            ClientTestHelper::json(200, self::page(['c'], false, 'c-c')),
        ]);

        $nodes = (new Pager($h->client, 2))->all('/sites/s1/workflows', ['environment' => 'dev']);

        $this->assertSame([['id' => 'a'], ['id' => 'b'], ['id' => 'c']], $nodes);
        $this->assertSame([
            'GET /v1/sites/s1/workflows?environment=dev&first=2',
            'GET /v1/sites/s1/workflows?environment=dev&first=2&after=c-b',
        ], $h->sent());
    }

    public function testCustomParameterNamesAndNestedConnection(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, ['id' => 'ws', 'sitesConnection' => self::page(['s1'], true, 'x')]),
            ClientTestHelper::json(200, ['id' => 'ws', 'sitesConnection' => self::page(['s2'], false, null)]),
        ]);

        $nodes = (new Pager($h->client, 50))->all(
            '/workspaces/ws',
            [],
            'workspace_sitesConnection_first',
            'workspace_sitesConnection_after',
            ['sitesConnection']
        );

        $this->assertSame([['id' => 's1'], ['id' => 's2']], $nodes);
        $this->assertSame(
            'GET /v1/workspaces/ws?workspace_sitesConnection_first=50&workspace_sitesConnection_after=x',
            $h->sent()[1]
        );
    }

    public function testStopsWhenNextPageHasNoCursor(): void
    {
        // Seen live on GET /sites/{id}/users?first=1.
        $h = new ClientTestHelper([ClientTestHelper::json(200, self::page([], true, null))]);
        $this->assertSame([], (new Pager($h->client))->all('/sites/s1/users'));
        $this->assertCount(1, $h->history);
    }

    public function testStopsWhenCursorRepeats(): void
    {
        $h = new ClientTestHelper([
            ClientTestHelper::json(200, self::page(['a'], true, 'same')),
            ClientTestHelper::json(200, self::page(['b'], true, 'same')),
        ]);
        $this->assertSame([['id' => 'a'], ['id' => 'b']], (new Pager($h->client))->all('/x'));
        $this->assertCount(2, $h->history);
    }

    public function testNonConnectionResponseThrows(): void
    {
        $h = new ClientTestHelper([ClientTestHelper::json(200, ['id' => 'not-a-list'])]);
        $this->expectException(PublicApiException::class);
        $this->expectExceptionMessage('expected a list');
        (new Pager($h->client))->all('/x');
    }

    public function testNodesSkipsEdgesWithoutNode(): void
    {
        $this->assertSame(
            [['id' => 'a']],
            Pager::nodes(['edges' => [['node' => ['id' => 'a']], ['cursor' => 'x', 'node' => null]]])
        );
    }
}
