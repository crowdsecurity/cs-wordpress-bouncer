# Run e2e tests

* Before all, be sure to [get the stack installed using the docker-compose guide](install-with-docker-compose.md).

> Note: If you have some problems while running tests, `docker system prune --volumes` can help.

> Note: When a test fail a screenshot is taken, find them in `./tests/e2e/screenshots`.

Headless mode (faster way):

```bash
./tests-local.sh
```

## Debug mode to view the browser in live

Debug mode (display the browser window and slow down speed. To use when adding new tests).

### Install e2e tests dependencies to automatically configure Wordpress and the CrowdSec plugin

* In order to run the excellent [Playwright](https://playwright.dev/) tool, you need to have `node` and `yarn` installed on your local host.

You have also have to install the required dependencies to run e2e tests with:

```bash
cd tests/e2e && yarn && cd -
```

```bash
DEBUG=1 ./tests-local.sh
```

> Note: you can add `await jestPlaywright.debug()` when you want to pause the process.

## Run all the versions:

```bash
./tests-local-wpall.sh
```