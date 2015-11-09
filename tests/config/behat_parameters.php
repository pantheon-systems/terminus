#!/usr/bin/php
<?php

$params = array(
  'username'                => 'devuser@pantheon.io',
  'password'                => 'password1',
  'host'                    => 'onebox',
  'vcr_mode'                => 'new_episodes',
  'test_site_name'          => 'behat-tests',
  'other_user'              => 'sara@getpantheon.com',
  'php_site_domain'         => 'onebox.pantheon.io',
  'payment_instrument_uuid' => '8558e04f-3674-481e-b448-bccff73cb430',
  'enterprise_org_uuid'     => 'bf200cbe-8995-4891-b5d4-1a8bdc292905',
  'enterprise_org_name'     => 'EnterpriseOrg',
);

$ci_params = array(
  'vcr_mode' => 'once',
);

$qa_params = array(
  'vcr_mode'       => null,
  'test_site_name' => 'saras-qa-test',
);

if (isset($argv[1])) {
  $var_name = sprintf('%s_params', $argv[1]);
  if (isset($$var_name)) {
    $params = array_merge($params, $$var_name);
  }
}

echo json_encode($params);
