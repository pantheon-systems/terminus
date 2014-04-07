<?php

namespace Pantheon\DataWrappers;

class Request {

  /*
   *
   *
   *
   *
   *
   *
   * */

  static function getResponse($realm, $uuid, $path = FALSE, $method = 'GET', $data = NULL, $responseClass = "Response") {
    if (empty($uuid)) {
      throw new \Pantheon\Exception("UUID not available during Terminus request call");
    }
    $cache = \Terminus::get_cache();
    $session = $cache->get_data('session');
    if ($session == FALSE) {
      throw new \Pantheon\Exception("You must log in first!");
    }
    static $ch = FALSE;
    if (!$ch) {
      $ch = curl_init();
    }
    $headers = array();
    $host = TERMINUS_HOST;
    if (strpos(TERMINUS_HOST, 'onebox') !== FALSE) {
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
      $host = TERMINUS_HOST;
    }
    $url = 'https://' . $host . '/terminus.php?' . $realm . '=' . $uuid;
    if ($path) {
      $url .= '&path=' . urlencode($path);
    }
    if (!empty($data)) {
      if (!is_array($data) && !is_object($data)) {
        $data = array($data);
      }
      // The $data for POSTs, PUTs, DELETEs are sent as JSON.
      if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        $data = json_encode(array('data' => $data));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);
        array_push($headers, 'Content-Type: application/json', 'Content-Length: ' . strlen($data));
      } // $data for GETs is sent as querystrings.
      else {
        if ($method === 'GET') {
          $url .= '?' . http_build_query($data);
        }
      }
    }
    // Set URL and other appropriate options.
    $opts = array(
      CURLOPT_URL => $url,
      CURLOPT_HEADER => 1,
      CURLOPT_PORT => TERMINUS_PORT,
      CURLOPT_RETURNTRANSFER => 1,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_CUSTOMREQUEST => $method,
      CURLOPT_COOKIE => $session->session,
      CURLOPT_HTTPHEADER => $headers,
    );
    curl_setopt_array($ch, $opts);

    $result = curl_exec($ch);
    list($headers_text, $json) = explode("\r\n\r\n", $result, 2);
    // Work around extra 100 Continue headers - http://stackoverflow.com/a/2964710/1895669
    if (strpos($headers_text, " 100 Continue") !== FALSE) {
      list($headers_text, $json) = explode("\r\n\r\n", $json, 2);
    }

    if (curl_errno($ch) != 0) {
      $error = curl_error($ch);
      curl_close($ch);
      \Terminus::error('TERMINUS_API_CONNECTION_ERROR', "CONNECTION ERROR: $error");
      return FALSE;
    }

    $info = curl_getinfo($ch);
    if ($info['http_code'] > 399) {
      if ($info['http_code'] == 403 && $json == '"Session not found."') {
        throw new \Pantheon\Exception("Session Expired.");
      } else {
        throw new \Pantheon\Exception("Request Failed.");
      }
      return FALSE;
    }
    $responseData = array(
      'info' => $info,
      'headers' => $headers_text,
      'json' => $json,
      'data' => json_decode($json)
    );
    $r = new \ReflectionClass('\Pantheon\Iterators\\' . $responseClass);
    return $r->newInstance($responseData);
  }

  

}