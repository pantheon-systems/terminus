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
     $data->session = 'alskdmca;sdlkcmas;dlc';
     $data->user_uuid = 'asdf;lkamsd';
     $data->session_expires_time = time(TRUE);
     $data->email = "test@email.com";

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
