#!/bin/bash
#export TERMINUS_HOST='onebox';
#php php/boot-fs.php auth logout || echo "ERROR LINE" $LINENO
#php php/boot-fs.php auth login wink@getpantheon.com --password=chicago77 || echo "ERROR LINE" $LINENO
#if  [[ $( php php/boot-fs.php sites show | grep 'behat-test' ) ]]; then
	#php php/boot-fs.php sites delete --site=behat-test --force
#fi
php php/boot-fs.php products all || echo "ERROR LINE" $LINENO
php php/boot-fs.php sites create --name=behat-test --label=behattest --product='wordpress' --org='d59379eb-0c23-429c-a7bc-ff51e0a960c2' || echo "ERROR LINE" $LINENO
php php/boot-fs.php site info --site=behat-test --nocache=1 || echo "ERROR LINE" $LINENO
php php/boot-fs.php site backup create --site=behat-test --env=dev --element=all || echo "ERROR LINE" $LINENO
php php/boot-fs.php site environments --site=behat-test || echo "ERROR LINE" $LINENO
php php/boot-fs.php site backup get --site=behat-test --env=dev --element=files --latest || echo "ERROR LINE" $LINENO
php php/boot-fs.php site code log --site=behat-test --env=dev || echo "ERROR LINE" $LINENO
php php/boot-fs.php site connection-mode --site=behat-test --env=dev || echo "ERROR LINE" $LINENO
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=git || echo "ERROR LINE" $LINENO
php php/boot-fs.php site connection-mode --site=behat-test --env=dev | grep -i "git" || echo "ERROR LINE" $LINENO
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=sftp || echo "ERROR LINE" $LINENO
php php/boot-fs.php site mount --site=behat-test --destination=/home/vagrant/mount --env=dev
echo "//testing" >> /home/vagrant/mount/code/index.php || echo "ERROR LINE" $LINENO
php php/boot-fs.php site code commit --site=behat-test --env=dev --message='Testing onserver code' --yes
php php/boot-fs.php site connection-mode --site=behat-test --env=dev --set=git || echo "ERROR LINE" $LINENO
php php/boot-fs.php site service-level --site=behat-test || echo "ERROR LINE" $LINENO
php php/boot-fs.php site service-level --site=behat-test --set=pro || echo "ERROR LINE" $LINENO
php php/boot-fs.php site lock info --site=behat-test --env=dev || echo "ERROR LINE" $LINENO
php php/boot-fs.php site lock add --site=behat-test --env=dev --username=test --password=test || echo "ERROR LINE" $LINENO
php php/boot-fs.php site lock remove --site=behat-test --env=dev --username=test --password=test || echo "ERROR LINE" $LINENO
php php/boot-fs.php site upstream-info --site=behat-test || echo "ERROR LINE" $LINENO
php php/boot-fs.php site wipe --site=behat-test --env=dev || echo "ERROR LINE" $LINENO
php php/boot-fs.php sites delete --site=behat-test --force || echo "ERROR LINE" $LINENO
