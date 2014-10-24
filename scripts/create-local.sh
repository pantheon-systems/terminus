#!/bin/bash
TERMINUS='/home/vagrant/cli/php/boot-fs.php'
LOCAL_DIR="/srv/www/drupal/$1"; #should be an empty dir
SITENAME=$1
DB="pantheon_$1"
DBUSER="$1"
DBPASS="$1"
SITE_ID=$( php $TERMINUS site info --site=$SITENAME --bash --nocache=1 | grep id | awk '{print $2}' )
GIT_REMOTE="ssh://codeserver.dev.$SITE_ID@codeserver.dev.$SITE_ID.drush.in:2222/~/repository.git"
echo "GIT_REMOTE=$GIT_REMOTE"

if [ ! -d $LOCAL_DIR ] ; then
	mkdir -p $LOCAL_DIR;
fi

cd $LOCAL_DIR

URLS=$( php $TERMINUS site backups-urls --site=$SITENAME --nocache --bash );
if [[ 1 = "$?" ]]; then
	echo "Must make a backup first \`terminus site backup-make --site=$SITENAME --env=dev\`"
	exit
fi

# code first
if [[ ! -d code ]]; then
	mkdir -p code
	cd code
	git clone $GIT_REMOTE .
	# setup git repo with remote connection
	git remote rm origin
	git remote add origin $GIT_REMOTE
	# reset and mode changes that may have been introduced
	git reset --hard HEAD
	cd ../
fi

DB_URL=$( echo "$URLS" | grep "database" )
curl "$DB_URL" > "database.sql.gz"
gunzip database.sql.gz

FILES_URL=$( echo "$URLS" | grep "files" )
curl "$FILES_URL" > files.tar.gz
mkdir -p files
tar -vxf files.tar.gz -C ./files/

# import db
mysql -e "DROP DATABASE $DB" #in case it already exists
mysql -e "create database $DB"
mysql -e "grant all privileges on $DB.* to $DBUSER@'%' identified by '$DBPASS'"
mysql $DB < database.sql

# create the local config if it's wordpress
cd code
if [[ -f ./wp-config.php ]]; then
	cp wp-config-sample.php wp-config-local.php
	sed -i 's/database_name_here/'$DB'/' wp-config-local.php
	sed -i 's/username_here/'$DBUSER'/' wp-config-local.php
	sed -i 's/password_here/'$DBPASS'/' wp-config-local.php
fi
