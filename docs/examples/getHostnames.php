<?php

require 'vendor/autoload.php';

use Terminus\Models\Collections\Sites;

$terminus  = new Terminus();
$sites     = new Sites();
$all_sites = $sites->all();
$domains   = array();

foreach ($all_sites as $site) {
  $environments = $site->environments->all();
  foreach ($environments as $environment) {
    $hostnames = (array)$environment->getHostnames();
    $domains   = array_merge(array_keys($hostnames), $domains);
  }
}

print_r($domains);
