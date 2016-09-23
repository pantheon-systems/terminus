<?php

require 'vendor/autoload.php';

use Terminus\Models\Collections\Sites;

$sites     = new Sites();
$all_sites = $sites->all();
$domains   = [];

foreach ($all_sites as $site) {
    $environments = $site->environments->all();
    foreach ($environments as $environment) {
        $hostnames = $environment->hostnames->ids();
        $domains   = array_merge($hostnames, $domains);
    }
}

print_r($domains);
