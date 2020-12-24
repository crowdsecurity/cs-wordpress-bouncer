#!/bin/bash

# Default variables
WATCHER_LOGIN='watcherLogin'
WATCHER_PASSWORD='watcherPassword'

# Run stack
docker-compose down --remove-orphans
docker-compose up -d wordpress$WORDPRESS_VERSION crowdsec mysql redis memcached

# Setup CrowdSec
export BOUNCER_KEY=`docker-compose exec crowdsec cscli bouncers add functional-tests -o raw`
export CS_WP_HOST=`docker-compose exec crowdsec /sbin/ip route|awk '/default/ { printf $3 }'`
docker-compose exec crowdsec cscli machines add $WATCHER_LOGIN --password $WATCHER_PASSWORD
echo "Waiting for WordPress container to initialize..."
until $(curl --output /dev/null --silent --head --fail http://localhost); do
    printf '.'
    sleep 0.1
done


# Run tests
WORDPRESS_VERSION=$WORDPRESS_VERSION WATCHER_LOGIN=$WATCHER_LOGIN WATCHER_PASSWORD=$WATCHER_PASSWORD \
LAPI_URL_FROM_CONTAINERS='http://crowdsec:8080' LAPI_URL_FROM_HOST='http://localhost:8080' \
yarn --cwd ./tests/functional test \
--detectOpenHandles --runInBand --json --outputFile=.test-results-$WORDPRESS_VERSION.json
