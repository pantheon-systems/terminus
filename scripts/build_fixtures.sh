#!/bin/bash
export BUILD_FIXTURES=1
find tests/fixtures -type f -exec rm {} \;
php php/boot-fs.php auth logout || exit 1
php php/boot-fs.php auth login wink@getpantheon.com --password=$DASH_PASS || exit 1
php php/boot-fs.php sites show || exit 1
php php/boot-fs.php products all || exit 1
php php/boot-fs.php sites create --name=behat-test --label=behattest --product='8c29aa27-21a3-4214-817e-c5c98c21b4cd' || exit 1
php php/boot-fs.php site info --site=behat-test --nocache=1 || exit 1
php php/boot-fs.php site backup-make --site=behat-test --env=dev || exit 1
php php/boot-fs.php site environments --site=behat-test || exit 1
php php/boot-fs.php site backups --site=behat-test || exit 1
php php/boot-fs.php site get-backup --site=behat-test --env=dev || exit 1
php php/boot-fs.php site wipe --site=behat-test --env=dev || exit 1
php php/boot-fs.php site diffstat --site=behat-test --dev=dev
php php/boot-fs.php sites delete --site=behat-test --force chi
export BUILD_FIXTURES=0
