<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Session\Session;
use Consolidation\Config\Config;

class SessionTest extends TestCase
{
    private function createSession(SavedTokens $tokens, array $configValues = []): Session
    {
        $dataStore = $this->createMock(DataStoreInterface::class);
        $dataStore->method('get')->willReturn([]);

        $session = new Session($dataStore);
        $session->setConfig(new Config($configValues));
        // SavedTokens::$tokens is public; Session::getTokens() uses it directly
        // if already set, so this avoids needing a real container/collection.
        $session->tokens = $tokens;

        return $session;
    }

    public function testGetAuthTokenWithSingleSavedToken()
    {
        $token = $this->createMock(SavedToken::class);
        $tokens = $this->createMock(SavedTokens::class);
        $tokens->method('all')->willReturn(['someone@example.com' => $token]);

        $session = $this->createSession($tokens);

        $this->assertSame($token, $session->getAuthToken());
    }

    public function testGetAuthTokenWithConfiguredUserEmail()
    {
        $matchingToken = $this->createMock(SavedToken::class);
        $otherToken = $this->createMock(SavedToken::class);
        $tokens = $this->createMock(SavedTokens::class);
        $tokens->method('all')->willReturn([
            'someone@example.com' => $otherToken,
            'match@example.com' => $matchingToken,
        ]);
        $tokens->method('get')->with('match@example.com')->willReturn($matchingToken);

        $session = $this->createSession($tokens, ['user' => 'match@example.com']);

        $this->assertSame($matchingToken, $session->getAuthToken());
    }

    public function testGetAuthTokenWithMultipleTokensAndNoConfiguredUserThrows()
    {
        $tokens = $this->createMock(SavedTokens::class);
        $tokens->method('all')->willReturn([
            'someone@example.com' => $this->createMock(SavedToken::class),
            'someone-else@example.com' => $this->createMock(SavedToken::class),
        ]);

        $session = $this->createSession($tokens);

        $this->expectException(TerminusException::class);
        $session->getAuthToken();
    }
}
