<?php

namespace Pantheon\Terminus\Tests\Unit;

use League\Container\Definition\DefinitionInterface;
use PHPUnit\Framework\TestCase;
use Pantheon\Terminus\Collections\SavedTokens;
use Pantheon\Terminus\DataStore\DataStoreInterface;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;

class SavedTokensTest extends TestCase
{
    private const EMAIL = 'someone@example.com';

    private function createCollection(bool $existing, DataStoreInterface &$store = null): SavedTokens
    {
        $user = $this->createMock(User::class);
        $user->method('get')->with('email')->willReturn(self::EMAIL);

        $token = $this->createMock(SavedToken::class);
        $token->method('logIn')->willReturn($user);

        $definition = $this->createMock(DefinitionInterface::class);
        $definition->method('addArguments')->willReturnSelf();
        $container = $this->createMock(\League\Container\Container::class);
        $container->method('add')->willReturn($definition);
        $container->method('get')->willReturn($token);

        $store = $this->createMock(DataStoreInterface::class);
        $store->method('has')->with(self::EMAIL)->willReturn($existing);

        $collection = new SavedTokens();
        $collection->setContainer($container);
        $collection->setDataStore($store);
        $this->token = $token;
        return $collection;
    }

    private $token;

    public function testCreateWithoutExistingTokenDoesNotConfirm()
    {
        $collection = $this->createCollection(false);
        $this->token->expects($this->once())->method('saveToDir');

        $collection->create('new-token', function () {
            $this->fail('Should not ask for confirmation when nothing will be overwritten.');
        });
    }

    public function testCreateOverwritesExistingTokenWhenConfirmed()
    {
        $collection = $this->createCollection(true);
        $this->token->expects($this->once())->method('saveToDir');

        $collection->create('new-token', fn (string $email): bool => $email === self::EMAIL);
    }

    public function testCreateDoesNotOverwriteExistingTokenWhenDeclined()
    {
        $collection = $this->createCollection(true);
        $this->token->expects($this->never())->method('saveToDir');

        $this->expectException(TerminusException::class);
        $this->expectExceptionMessage('--yes');
        $collection->create('new-token', fn (): bool => false);
    }
}
