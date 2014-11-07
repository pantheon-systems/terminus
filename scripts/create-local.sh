#!/bin/bash
TERMINUS='php /home/vagrant/cli/php/boot-fs.php'
LOCAL_DIR="/srv/www/drupal/$1"; #should be an empty dir
SITENAME=$1
DB_NAME="pantheon_$( echo $1 | sed -r 's/-//')"
DBUSER="$( echo $1 | sed -r 's/-//')"
DBPASS="$( echo $1 | sed -r 's/-//')"
SITE_ID=$( $TERMINUS site info --site=$SITENAME --bash --nocache=1 | grep id | awk '{print $2}' )
GIT_REMOTE="ssh://codeserver.dev.$SITE_ID@codeserver.dev.$SITE_ID.drush.in:2222/~/repository.git"
echo "GIT_REMOTE=$GIT_REMOTE"

if [ ! -d $LOCAL_DIR ] ; then
	mkdir -p $LOCAL_DIR;
fi

cd $LOCAL_DIR

$TERMINUS site get-backup --site=$SITENAME --element=files --env='dev' --to-directory=$LOCAL_DIR || exit 1
$TERMINUS site get-backup --site=$SITENAME --element=database --env='dev' --to-directory=$LOCAL_DIR || exit 1

DB=$( ls . | grep "database.*gz" )
FILES=$( ls . | grep "files.*gz" )

# code first
if [[ ! -d code ]]; then
	mkdir -p code
fi

	cd code
	git clone $GIT_REMOTE .
	# setup git repo with remote connection
	git remote rm origin
	git remote add origin $GIT_REMOTE
	# reset and mode changes that may have been introduced
	git reset --hard HEAD
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
cd code
if [[ -f ./wp-config.php ]]; then
	cp wp-config-sample.php wp-config-local.php
	sed -i 's/database_name_here/'$DB_NAME'/' wp-config-local.php
	sed -i 's/username_here/'$DBUSER'/' wp-config-local.php
	sed -i 's/password_here/'$DBPASS'/' wp-config-local.php
fi

echo "All done!"
