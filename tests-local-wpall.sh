#!/bin/bash

./tests-local.sh
./tests-local-wp5.5.sh
./tests-local-wp5.4.sh
./tests-local-wp5.3.sh
./tests-local-wp5.2.sh
./tests-local-wp5.1.sh
./tests-local-wp5.0.sh
./tests-local-wp4.9.sh

cat tests/e2e/.test-results-* | jq .success