<?php
/**
 * Testing class for \Terminus\Session
 *
 */
use Terminus\Session;

 class SessionTest extends PHPUnit_Framework_TestCase {

   public function testSession() {
     if (getenv("BUILD_FIXTURES") == 1) return true;
     $data = new stdClass();
     $data->session = 'dca4f8cd-9ec2-4117-957f-fc5230c23737:7c468270-66e9-11e4-aa9b-bc764e111d20:eEFVhz2Dc87oZIJxqBtaM';
     $data->user_uuid = 'dca4f8cd-9ec2-4117-957f-fc5230c23737';
     $data->session_expires_time = 1421186762;
     $data->email = "mike+test@mikevanwinkle.com";

     Session::instance()->setData($data);
     $session = Session::instance();
     $this->assertInstanceOf('\Terminus\Session', $session);
     $this->assertEquals($data->session,$session->get('session'));
     $this->assertEquals($data->user_uuid,$session->get('user_uuid'));
     $this->assertEquals($data->email,$session->get('email'));
     $this->assertEquals($data->session_expires_time,$session->get('session_expires_time'));

     // Test static methods
     $this->assertEquals($data->session,Session::getValue('session'));
     $this->assertEquals($data->user_uuid,Session::getValue('user_uuid'));
     $this->assertEquals($data->email,Session::getValue('email'));
     $this->assertEquals($data->session_expires_time,Session::getValue('session_expires_time'));


   }

 }
