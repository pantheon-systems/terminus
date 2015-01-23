<?php
/**
 * Testing class for \Terminus\Session
 *
 */
use Terminus\Session;

 class SessionTest extends PHPUnit_Framework_TestCase {

   public function testSession() {

     $data = Session::getData();

     Session::instance()->setData($data);
     $session = Session::instance();
     $this->assertInstanceOf('\Terminus\Session', $session);
     $this->assertEquals($data->session,$session->get('session'));
     $this->assertEquals($data->user_uuid,$session->get('user_uuid'));
     $this->assertEquals($data->email,$session->get('email'));

     // Test static methods
     $this->assertEquals($data->session,Session::getValue('session'));
     $this->assertEquals($data->user_uuid,Session::getValue('user_uuid'));
     $this->assertEquals($data->email,Session::getValue('email'));

   }

 }
