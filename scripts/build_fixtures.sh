#!/bin/bash
export TERMINUS_HOST='onebox';
php php/boot-fs.php auth logout || exit 1
php php/boot-fs.php auth login wink@getpantheon.com --password=chicago77 || exit 1
if  [[ $( php php/boot-fs.php sites show | grep 'behat-test' ) ]]; then
	php php/boot-fs.php sites delete --site=behat-test --force 
fi
php php/boot-fs.php products all || exit 1
php php/boot-fs.php sites create --name=behat-test --label=behattest --product='8c29aa27-21a3-4214-817e-c5c98c21b4cd' || exit 1
php php/boot-fs.php site info --site=behat-test --nocache=1 || exit 1
php php/boot-fs.php site backup create --site=behat-test --env=dev || exit 1
php php/boot-fs.php site environments --site=behat-test || exit 1
php php/boot-fs.php site backup get --site=behat-test --env=dev || exit 1
php php/boot-fs.php site code log --site=behat-test || exit 1
php php/boot-fs.php site connection-mode --site=behat-test --env=dev 
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=git
php php/boot-fs.php site connection-mode --site=behat-test --env=dev | grep "git" || exit 1
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=sftp
php php/boot-fs.php site mount --site=behat-test --destination=/home/vagrant/mount --env=dev
echo "//testing" >> /home/vagrant/mount/code/index.php
php php/boot-fs.php site code commit --site=behat-test --env-dev --message='Testing onserver code'
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=git
php php/boot-fs.php site service-level --site=behat-test 
php php/boot-fs.php site redis --site=behat-test
php php/boot-fs.php site newrelic --site=behat-test
php php/boot-fs.php site lock info --site=behat-test --env=dev
php php/boot-fs.php site lock add --site=behat-test --env=dev --username=test --password=test
php php/boot-fs.php site lock remove --site=behat-test --env=dev --username=test --password=test
php php/boot-fs.php site upstream-info --site=behat-test
php php/boot-fs.php site dashboard --site=behat-test
php php/boot-fs.php site clear-caches --site=behat-test --env=dev
php php/boot-fs.php site get-backup --site=behat-test --env=dev || exit 1
php php/boot-fs.php site wipe --site=behat-test --env=dev || exit 1
php php/boot-fs.php sites delete --site=behat-test --force || exit 1
unset -v TERMINUS_HOST
