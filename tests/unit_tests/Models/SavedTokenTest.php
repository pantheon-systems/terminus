<?php


namespace Pantheon\Terminus\UnitTests\Models;

use Pantheon\Terminus\Config;
use Pantheon\Terminus\Models\SavedToken;
use Pantheon\Terminus\Models\User;
use Pantheon\Terminus\Session\Session;
use Pantheon\Terminus\Exceptions\TerminusException;

class SavedTokenTest extends ModelTestCase
{

    public function testConstuct()
    {
        $token = new SavedToken((object)['email' => 'dev@example.com']);
        $this->assertEquals('dev@example.com', $token->id);
    }


    public function testLogIn()
    {
        $token = new SavedToken((object)['token' => '123']);

        $session_data = ['session' => '123', 'expires_at' => 12345];
        $this->request->expects($this->once())
            ->method('request')
            ->with('authorize/machine-token', [
                'form_params' => ['machine_token' => '123', 'client' => 'terminus',],
                'method' => 'post',
            ])
            ->willReturn(['data' => (object)$session_data]);

        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session->expects($this->once())
            ->method('setData')
            ->with($session_data);

        $user = new User();
        $session->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $token->setRequest($this->request);
        $token->setSession($session);
        $out = $token->logIn();
        $this->assertEquals($user, $out);
    }

    public function testSaveToDir()
    {
        // Create a temp directory to write to.
        // @TODO: Separate file writing so that this test can be run without writing to disk.
        $dir = tempnam(sys_get_temp_dir(), 'savedtoken_');
        unlink($dir);
        mkdir($dir);

        $attributes = ['email' => 'dev@example.com', 'token' => '123'];
        $token = new SavedToken((object)$attributes);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('tokens_dir')
            ->willReturn($dir);

        $token->setConfig($config);

        $token->saveToDir();
        $file = "$dir/dev@example.com";
        $this->assertFileExists($file);
        $file_attributes = json_decode(file_get_contents($file));
        foreach ($attributes as $key => $val) {
            $this->assertEquals($val, $file_attributes->{$key}, 'Mismatch on key ' . $key);
        }

        // Clean up
        unlink($file);
        rmdir($dir);
    }

    public function testDelete()
    {
        // Create a temp directory to write to.
        // @TODO: Separate file writing so that this test can be run without writing to disk.
        $dir = tempnam(sys_get_temp_dir(), 'savedtoken_');
        unlink($dir);
        mkdir($dir);

        $attributes = ['email' => 'dev@example.com', 'token' => '123'];
        $token = new SavedToken((object)$attributes);

        $attributes = ['email' => 'dev2@example.com', 'token' => '234'];
        $token2 = new SavedToken((object)$attributes);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->any())
            ->method('get')
            ->with('tokens_dir')
            ->willReturn($dir);

        $token->setConfig($config);
        $token2->setConfig($config);

        $token->saveToDir();
        $token2->saveToDir();

        $file = "$dir/dev@example.com";
        $file2 = "$dir/dev2@example.com";
        $this->assertFileExists($file);
        $this->assertFileExists($file2);
        $token->delete();
        $this->assertFileNotExists($file);
        $this->assertFileExists($file2);

        // Clean up
        unlink($file2);
        rmdir($dir);
    }

    public function testInvalidID()
    {
        $dir = tempnam(sys_get_temp_dir(), 'savedtoken_');
        unlink($dir);
        mkdir($dir);

        $attributes = ['email' => '', 'token' => '123'];
        $token = new SavedToken((object)$attributes);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('tokens_dir')
            ->willReturn($dir);

        $token->setConfig($config);

        $this->setExpectedException(
            TerminusException::class,
            'Could not save the machine token because it is missing an ID'
        );

        $token->saveToDir();


        $this->assertEquals(['.', '..'], scandir($dir));

        rmdir($dir);
    }

    public function testInvalidPath()
    {
        $dir = '';

        $attributes = ['email' => 'dev@example.com', 'token' => '123'];
        $token = new SavedToken((object)$attributes);

        $config = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->once())
            ->method('get')
            ->with('tokens_dir')
            ->willReturn($dir);

        $token->setConfig($config);

        $this->setExpectedException(
            TerminusException::class,
            'Could not save the machine token because the token path is mis-configured'
        );

        $token->saveToDir();
    }
}
