#!/bin/bash
export BUILD_FIXTURES=1
find fixtures -type f -exec rm {} \;
php php/boot-fs.php auth logout || exit 1
php php/boot-fs.php auth login wink+behat@getpantheon.com --password=chicago77 || exit 1
php php/boot-fs.php sites show || exit 1
php php/boot-fs.php products all || exit 1
php php/boot-fs.php sites create --name=behat-test --label=behattest --product='e8fe8550-1ab9-4964-8838-2b9abdccf4bf' || exit 1
php php/boot-fs.php site info --site=behat-test || exit 1
php php/boot-fs.php site backup_make --site=behat-test --env=dev || exit 1
php php/boot-fs.php site environments --site=behat-test || exit 1
php php/boot-fs.php site backups --site=behat-test || exit 1
php php/boot-fs.php site backups_urls --site=behat-test || exit 1
php php/boot-fs.php sites delete --site=behat-test --force || exit 1
export BUILD_FIXTURES=0
