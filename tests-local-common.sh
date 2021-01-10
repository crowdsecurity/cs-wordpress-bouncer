#!/bin/bash

# Default variables
export WATCHER_LOGIN='watcherLogin'
export WATCHER_PASSWORD='watcherPassword'
export WORDPRESS_URL=0 # as each docker-compose execution require it
CONTAINER_NAME=`echo "wordpress$WORDPRESS_VERSION" | tr . -`

# Run stack
export BOUNCER_KEY=0 # as each docker-compose execution require it
docker-compose down --remove-orphans
docker-compose up -d $CONTAINER_NAME crowdsec mysql redis memcached
docker-compose exec $CONTAINER_NAME composer install --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source

# Setup CrowdSec
export BOUNCER_KEY=`docker-compose exec crowdsec cscli bouncers add e2e-tests -o raw`
export CS_WP_HOST=`docker-compose exec crowdsec /sbin/ip route|awk '/default/ { printf $3 }'`
docker-compose exec crowdsec cscli machines add $WATCHER_LOGIN --password $WATCHER_PASSWORD
echo "Waiting for WordPress container to initialize..."
until $(curl --output /dev/null --silent --head --fail http://localhost:8050); do
    printf '.'
    sleep 0.1
done

# Run tests

rm -rf ./tests/e2e/screenshots
cd logs && rm -R `ls -1 -d */` ; cd -

# If "SETUP_ONLY" is passed, run only setup of Wordpress and the CrowdSec plugin
if [[ -z "${SETUP_ONLY}" ]]; then
    FILELIST=""
    echo "RUN ALL STEPS"
else
    FILELIST="./__tests__/0-setup-wordpress.js ./__tests__/1-setup-plugin.js"
    echo "RUN SETUP STEPS ONLY"
fi

if [[ -z "${DEBUG}" ]]; then
    
    echo "(debug mode disabled)"
    docker-compose run e2e yarn --cwd ./var/run/tests

    WORDPRESS_URL="http://$CONTAINER_NAME" \
    NETWORK_IFACE=eth0 \
    docker-compose run e2e yarn --cwd ./var/run/tests test \
    --detectOpenHandles \
    --runInBand \
    --json \
    --outputFile=/var/run/tests/.test-results-$WORDPRESS_VERSION.json \
    $FILELIST
    
else

    echo "DEBUG MODE ENABLED"
    cd tests/e2e && yarn && cd -

    WORDPRESS_URL="http://localhost:8050" \
    BROWSER_IP=$CS_WP_HOST \
    WORDPRESS_VERSION=$WORDPRESS_VERSION \
    WATCHER_LOGIN=$WATCHER_LOGIN \
    WATCHER_PASSWORD=$WATCHER_PASSWORD \
    LAPI_URL_FROM_WP='http://crowdsec:8080' \
    LAPI_URL_FROM_E2E='http://localhost:8051' \
    yarn \
    --cwd ./tests/e2e \
    test \
    --detectOpenHandles \
    --runInBand \
    --json \
    --outputFile=.test-results-$WORDPRESS_VERSION.json \
    $FILELIST
fi
