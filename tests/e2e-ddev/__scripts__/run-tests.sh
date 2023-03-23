#!/bin/bash
# Run test suite
# Usage: ./run-tests.sh  <type>  <file-list>
# type : host, docker or ci (default: host)
# file-list : a list of test files (default: empty so it will run all the tests)
# Example: ./run-tests.sh docker "./__tests__/2-live-mode-remediations.js ./__tests__/3-live-mode-more.js"

YELLOW='\033[33m'
RESET='\033[0m'
if ! ddev --version >/dev/null 2>&1; then
    printf "${YELLOW}Ddev is required for this script. Please see doc/ddev.md.${RESET}\n"
    exit 1
fi


TYPE=${1:-host}
FILE_LIST=${2:-""}


case $TYPE in
  "host")
    echo "Running with host stack"
    ;;

  "docker")
    echo "Running with ddev docker stack"
    ;;


  "ci")
    echo "Running in CI context"
    ;;

  *)
    echo "Unknown param '${TYPE}'"
    echo "Usage: ./run-tests.sh  <type>  <file-list>"
    exit 1
    ;;
esac


HOSTNAME=$(ddev exec printenv DDEV_HOSTNAME | sed 's/\r//g')
WORDPRESS_VERSION=$(ddev exec printenv DDEV_PROJECT | sed 's/\r//g' | sed 's/wp//g')
WORDPRESS_URL=https://$HOSTNAME
PROXY_IP=$(ddev find-ip ddev-router)
BOUNCER_KEY=$(ddev exec wp option get crowdsec_api_key | tail -n 2 | head -n 1 | sed 's/\r//g')
JEST_PARAMS="--bail=true  --runInBand --verbose"
TLS_PATH="tls" # Relative to var path
# If FAIL_FAST, will exit on first individual test fail
# @see CustomEnvironment.js
FAIL_FAST=true


case $TYPE in
  "host")
    CROWDSEC_URL_FROM_HOST=$(ddev describe | grep -A 1 "crowdsec" | sed 's/Host: //g' |  sed -e 's|│||g' | sed s/'\s'//g | tail -1)
    cd "../"
    DEBUG_STRING="PWDEBUG=1"
    YARN_PATH="./"
    COMMAND="yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=https://${CROWDSEC_URL_FROM_HOST}
    CURRENT_IP=$(ddev find-ip host)
    TIMEOUT=60000
    HEADLESS=false
    SLOWMO=150
    PLUGIN_PATH="../../"
    ;;

  "docker")
    DEBUG_STRING=""
    YARN_PATH="./var/www/html/wp-content/plugins/crowdsec/tests/e2e-ddev"
    COMMAND="ddev exec -s playwright yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=https://crowdsec:8080
    CURRENT_IP=$(ddev find-ip playwright)
    TIMEOUT=60000
    HEADLESS=true
    SLOWMO=0
    PLUGIN_PATH="/var/www/html/wp-content/plugins/crowdsec"
    ;;

  "ci")
    DEBUG_STRING="DEBUG=pw:api"
    YARN_PATH="./var/www/html/wp-content/plugins/crowdsec/tests/e2e-ddev"
    COMMAND="ddev exec -s playwright xvfb-run --auto-servernum -- yarn --cwd ${YARN_PATH} cross-env"
    LAPI_URL_FROM_PLAYWRIGHT=https://crowdsec:8080
    CURRENT_IP=$(ddev find-ip playwright)
    TIMEOUT=60000
    HEADLESS=true
    SLOWMO=0
    PLUGIN_PATH="/var/www/html/wp-content/plugins/crowdsec"
    ;;

  *)
    echo "Unknown param '${TYPE}'"
    echo "Usage: ./run-tests.sh  <type>  <file-list>"
    exit 1
    ;;
esac



# Run command

$COMMAND \
WORDPRESS_URL=$WORDPRESS_URL \
WORDPRESS_VERSION=$WORDPRESS_VERSION \
$DEBUG_STRING \
BOUNCER_KEY=$BOUNCER_KEY \
PROXY_IP=$PROXY_IP  \
LAPI_URL_FROM_PLAYWRIGHT=$LAPI_URL_FROM_PLAYWRIGHT \
CURRENT_IP=$CURRENT_IP \
TIMEOUT=$TIMEOUT \
HEADLESS=$HEADLESS \
FAIL_FAST=$FAIL_FAST \
SLOWMO=$SLOWMO \
TLS_PATH=$TLS_PATH \
PLUGIN_PATH=$PLUGIN_PATH \
yarn --cwd $YARN_PATH test \
    $JEST_PARAMS \
    --json \
    --outputFile=./.test-results.json \
    $FILE_LIST
