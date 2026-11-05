<?php

namespace Pantheon\Terminus\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Request\Request;
use Pantheon\Terminus\Request\RequestOperationResult;
use Pantheon\Terminus\Session\Session;

class SavedTokenTest extends TestCase
{
    private function createSavedToken(Request $request, Session $session): SavedToken
    {
        $token = new SavedToken((object)['token' => 'fake-machine-token']);
        $token->setRequest($request);
        $token->setSession($session);
        return $token;
    }

    public function testLogInThrowsOnNonSuccessfulResponse()
    {
        $request = $this->createMock(Request::class);
        $request->method('requestWithoutRefreshHandling')->willReturn(
            new RequestOperationResult([
                'data' => (object)['message' => 'invalid machine token'],
                'headers' => [],
                'status_code' => 401,
                'status_code_reason' => 'Unauthorized',
            ])
        );
        $session = $this->createMock(Session::class);
        $session->expects($this->never())->method('setData');

        $token = $this->createSavedToken($request, $session);

        $this->expectException(TerminusException::class);
        $token->logIn();
    }

    public function testLogInSucceedsOnSuccessfulResponse()
    {
        $request = $this->createMock(Request::class);
        $request->method('requestWithoutRefreshHandling')->willReturn(
            new RequestOperationResult([
                'data' => (object)['session' => 'new-session-token', 'user_id' => 'user-123'],
                'headers' => [],
                'status_code' => 200,
                'status_code_reason' => 'OK',
            ])
        );

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('setData')->with([
            'session' => 'new-session-token',
            'user_id' => 'user-123',
        ]);
        $user = $this->createMock(User::class);
        $session->method('getUser')->willReturn($user);

        $token = $this->createSavedToken($request, $session);

        $this->assertSame($user, $token->logIn());
    }
}
