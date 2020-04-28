#!/bin/bash

# must have a running container named share-c3d-io

open -g "https://login.operatoroverload.com/authorize?client_id=47ile8emo7m8flnhjfuc5aa9i0&response_type=code&scope=email+openid+profile&redirect_uri=http://localhost:3000/"

RESULT=$(echo 'hello' | nc -l 3000)

CODE=$(echo "$RESULT" | grep GET | sed -E 's/.*code=(.*)?[[:space:]]HTTP.*/\1/')

TOKEN=$(curl --data "code=$CODE&grant_type=authorization_code&client_id=47ile8emo7m8flnhjfuc5aa9i0&redirect_uri=http://localhost:3000/" \
     https://login.operatoroverload.com/token | json_pp | grep id_token | sed -E 's/.*:.*\"(.*)\".*/\1/')

docker exec -e ID_TOKEN="$TOKEN" share-c3d-io phpunit user/plugins/share-c3d-io-plugin/tests.php
