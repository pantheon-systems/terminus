<?php
/**
 * @file
 * PHPUnit Tests for Terminus using Drush's test framework.
 */

class pantheonAuthenticate extends Drush_UnitTestCase {
  /**
   * Load Terminus.
   */
  public function __construct() {
    parent::__construct();
    // Load Terminus.
    require_once __DIR__ . '/../terminus.drush.inc';

  }

  public function testPantheonAuthenticate() {
    // Skip all prompts.
    drush_set_context('DRUSH_AFFIRMATIVE', TRUE);

    // No email.
    $this->assertFalse(drush_terminus_pantheon_auth_validate(), FALSE);

    // Invalid email.
    $this->assertFalse(drush_terminus_pantheon_auth_validate('fail'), FALSE);

    // Valid email.
    $this->assertTrue(drush_terminus_pantheon_auth_validate('user@example.com'), TRUE);

    // Google emails.
    $this->assertTrue(drush_terminus_pantheon_auth_validate('user+filter@example.com'), TRUE);

    // Form parsing.
    $form = '<form action="/login" method="post" id="atlas-login-form" accept-charset="UTF-8"><div><h2 class="pane-title">Login here unless you <a href="/password">forgot your password</a> or <a href="/register">need to register</a>.</h2><div class="form-item-wrapper clearfix"><div class="form-label"><span class="form-label-text">Email Address</span></div><div class="form-item form-type-textfield form-item-email"> <input class="jsvalidate-enabled form-text" rule="{&quot;required&quot;:&quot;true&quot;,&quot;email&quot;:&quot;true&quot;}" message="{&quot;required&quot;:&quot;Please provide an email address.&quot;,&quot;email&quot;:&quot;The email address is not valid.&quot;}" type="text" id="edit-email" name="email" value="" size="60" maxlength="128" /> </div> <div class="form-error-wrapper"><div class="error-wrapper"></div><div class="form-error-arrow"></div></div></div><div class="form-item-wrapper clearfix"><div class="form-label"><span class="form-label-text">Password</span></div><div class="form-item form-type-password form-item-password"> <input class="jsvalidate-enabled form-text" rule="{&quot;required&quot;:&quot;true&quot;}" message="{&quot;required&quot;:&quot;Please provide a password.&quot;}" type="password" id="edit-password" name="password" size="60" maxlength="128" /> </div> <div class="form-error-wrapper"><div class="error-wrapper"></div><div class="form-error-arrow"></div></div></div><div class="form-item-wrapper clearfix"><div class="form-label"><span class="form-label-text"></span></div><input type="submit" id="edit-submit" name="op" value="Login" class="form-submit" /><div class="form-error-wrapper"><div class="error-wrapper"></div><div class="form-error-arrow"></div></div></div><input type="hidden" name="form_build_id" value="form-NEOwKyC4yaJIwMuLpkRG8xMdCbFK1--E8j8FgLvADdg" /> <input type="hidden" name="form_id" value="atlas_login_form" /> </div></form>';
    $this->assertEquals(terminus_pauth_login_get_form_build_id($form), 'form-NEOwKyC4yaJIwMuLpkRG8xMdCbFK1--E8j8FgLvADdg');

    // Fake headers for validation.
    $headers = array();
    $headers[] = "HTTP/1.1 100 Continue\r\n";
    $headers[] = "HTTP/1.1 302 Moved Temporarily";
    $headers[] = "Server: nginx";
    $headers[] = "Date: Thu, 26 Sep 2013 23:24:30 GMT";
    $headers[] = "Content-Type: text/html";
    $headers[] = 'Transfer-Encoding: chunked';
    $headers[] = 'Connection: keep-alive';
    $headers[] = 'X-Powered-By: PHP/5.3.17';
    $headers[] = 'Expires: Sun, 19 Nov 1978 05:00:00 GMT';
    $headers[] = 'Last-Modified: Thu, 26 Sep 2013 23:24:30 +0000';
    $headers[] = 'Cache-Control: no-cache, must-revalidate, post-check=0, pre-check=0';
    $headers[] = 'ETag: "1380237870"';
    $headers[] = 'Set-Cookie: SSESSnicetry; expires=Sun, 20-Oct-2013 02:57:50 GMT; path=/; domain=.terminus.getpantheon.com; secure; HttpOnly';
    $headers[] = 'Location: https://terminus.getpantheon.com/users/12345678-90ab-cdef-1234-567890abcdef';
    $headers[] = "";
    $headers = implode("\r\n", $headers);

    // Set cookie header parsing.
    $set_cookie_header = terminus_parse_drupal_headers($headers, 'Set-Cookie');
    $this->assertEquals('SSESSnicetry; expires=Sun, 20-Oct-2013 02:57:50 GMT; path=/; domain=.terminus.getpantheon.com; secure; HttpOnly', $set_cookie_header);

    // Session parsing.
    $session = terminus_pauth_get_session_from_header($set_cookie_header);
    $this->assertEquals('SSESSnicetry', $session);

    // User UUID parsing.
    $user_uuid = terminus_pauth_get_user_uuid_from_headers($headers);
    $this->assertEquals('12345678-90ab-cdef-1234-567890abcdef', $user_uuid);

    // Validate the UUID.
    $this->assertTrue(terminus_validate_uuid($user_uuid));

    // Invalid UUID.
    $this->assertFalse(terminus_validate_uuid('fail'));
  }
}