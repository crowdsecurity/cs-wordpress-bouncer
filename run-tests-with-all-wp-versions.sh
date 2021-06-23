#!/bin/bash

WORDPRESS_VERSION=5.7 ./run-tests.sh
WORDPRESS_VERSION=5.6 ./run-tests.sh
WORDPRESS_VERSION=5.5 ./run-tests.sh
WORDPRESS_VERSION=5.4 ./run-tests.sh
WORDPRESS_VERSION=5.3 ./run-tests.sh
WORDPRESS_VERSION=5.2 ./run-tests.sh
WORDPRESS_VERSION=5.1 ./run-tests.sh
WORDPRESS_VERSION=5.0 ./run-tests.sh
WORDPRESS_VERSION=4.9 ./run-tests.sh

cat tests/e2e/.test-results-* | jq .success