#!/bin/bash

# Must have a .env file with all the YOURLS environment variables set
source .env

if [ "$1" == "prod" ]
then
    docker run -e YOURLS_SITE=$YOURLS_SITE\
	   -e YOURLS_USER=$YOURLS_USER \
	   -e YOURLS_PASS=$YOURLS_PASS \
	   -e YOURLS_DB_HOST=$YOURLS_DB_HOST \
	   -e YOURLS_DB_NAME=$YOURLS_DB_NAME \
	   -e YOURLS_DB_USER=$YOURLS_DB_USER \
	   -e YOURLS_DB_PASS=$YOURLS_DB_PASS \
	   -p 8080:80 \
	   --name share-c3d-io \
	   --rm env3d/share-c3d-io 
else
    docker run -e YOURLS_SITE=$YOURLS_SITE\
	   -e YOURLS_USER=$YOURLS_USER \
	   -e YOURLS_PASS=$YOURLS_PASS \
	   -e YOURLS_DB_HOST=$YOURLS_DB_HOST \
	   -e YOURLS_DB_NAME=$YOURLS_DB_NAME \
	   -e YOURLS_DB_USER=$YOURLS_DB_USER \
	   -e YOURLS_DB_PASS=$YOURLS_DB_PASS \
	   -e YOURLS_DEBUG=true \
	   -v "$(pwd)"/share-c3d-io-plugin:/var/www/html/user/plugins/share-c3d-io-plugin \
	   -p 8080:80 \
	   --name share-c3d-io \
	   --rm -it env3d/share-c3d-io $@
fi

   
