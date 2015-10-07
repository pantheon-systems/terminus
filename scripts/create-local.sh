#!/bin/bash
SITENAME=${1:?"You must enter the name of the site you want to install."}
TERMINUS='php /home/vagrant/cli/php/boot-fs.php'
LOCAL_DIR="/srv/www/$1"; #should be an empty dir
DB_NAME="pantheon_$( echo $1 | sed -r 's/-//g')"
DBUSER="$( echo $1 | sed -r 's/-//g')"
DBPASS="$( echo $1 | sed -r 's/-//g')"
SITE_ID=$( $TERMINUS site info --site=$SITENAME --format=bash | grep ^id | awk '{print $2}' )
GIT_REMOTE="ssh://codeserver.dev.$SITE_ID@codeserver.dev.$SITE_ID.drush.in:2222/~/repository.git"
echo "GIT_REMOTE=$GIT_REMOTE"

if [ ! -d $LOCAL_DIR ] ; then
	mkdir -p $LOCAL_DIR;
fi

cd $LOCAL_DIR

$TERMINUS site backup get --site=$SITENAME --element=files --env='dev' --to=$LOCAL_DIR --latest || exit 1
$TERMINUS site backup get --site=$SITENAME --element=db --env='dev' --to=$LOCAL_DIR --latest || exit 1

DB=$( ls . | grep "database.*gz" )
FILES=$( ls . | grep "files.*gz" )

# code first
if [[ ! -d code ]]; then
	mkdir -p code
fi

cd code

if [ -e .git/config ]; then
	git pull origin master
else
	git clone $GIT_REMOTE .
	# setup git repo with remote connection
	git remote rm origin
	git remote add origin $GIT_REMOTE
	# reset and mode changes that may have been introduced
	git reset --hard HEAD
fi

cd ../

# import files
tar -vxf $FILES

# import db
gunzip $DB

DB=$( ls . | grep "database" )
echo DATABASE=$DB
mysql -e "DROP DATABASE $DB_NAME" #in case it already exists
mysql -e "create database $DB_NAME"
mysql -e "grant all privileges on $DB_NAME.* to $DBUSER@'%' identified by '$DBPASS'"
mysql -vvv $DB_NAME < $DB

# create the local config if it's wordpress
framework=""
cd code
if [[ -f ./wp-config.php ]]; then
	cp wp-config-sample.php wp-config-local.php
	sed -i 's/database_name_here/'$DB_NAME'/' wp-config-local.php
	sed -i 's/username_here/'$DBUSER'/' wp-config-local.php
	sed -i 's/password_here/'$DBPASS'/' wp-config-local.php
	framework="wp"
elif [[ -f ./sites/default/default.settings.php ]]; then
	cp ./sites/default/default.settings.php ./sites/default/settings.php
	# create a local settings file
	touch ./sites/default/local.settings.php
	# inject include
	echo "if (file_exists(__DIR__.'/local.settings.php')) require_once __DIR__.'/local.settings.php'; // inserted by create-local.sh" >> ./sites/default/settings.php
	echo "<?php
		\$databases['default']['default'] = array(
			'driver' => 'mysql',
			'database' => '$DB_NAME',
			'username' => '$DBUSER',
			'password' => '$DBPASS',
			'host'	=> 'localhost',
			'post'	=> '3306',
		);" >> ./sites/default/local.settings.php
	framework="drupal"
fi
# maybe do the nginx conf if we're set up for it
if [ -f /etc/nginx/nginx-$framework-common.conf ]; then
	sudo sh -c "echo '
	server {
	    listen       80;
	    listen       443 ssl;
	    server_name  local.$SITENAME.dev;
	    root         /srv/www/$SITENAME/code;
	    include      /etc/nginx/nginx-$framework-common.conf;
	}' > /etc/nginx/custom-sites/$SITENAME.conf"

	# restart nginx but only if the config test passes
	sudo nginx -t && sudo service nginx restart
fi

ipaddress=$( ifconfig eth1 | grep 'inet addr:' | cut -d: -f2 | awk '{print $1}' )
echo "All done, but don't forget you'll need to add the line below to your computers /etc/hosts file in order to view the site with a web browsers

$ipaddress local.$SITENAME.dev"
