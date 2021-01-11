# Run e2e tests

* Before all, be sure to [get the stack installed using the docker-compose guide](install-with-docker-compose.md).

> In order to run the excellent [Playwright](https://playwright.dev/) tool, you need to have `node` and `yarn` installed on your local host.

## A) Headful mode

The debug mode display the browser window and slow down test execution speed. Use this mode when you add new tests.

Run:
```bash
./run-tests.sh
```

> If you have some problems while running tests, `docker system prune --volumes` can help.

> When a test fail a screenshot is taken, find them in `./tests/e2e/screenshots`.

> You can add `await jestPlaywright.debug()` when you want to pause the process.

## B) Headless mode (faster way)

To run the e2e tests without debug mode (in a docker container) just replace in the `.env` file:

```bash
DEBUG=1
```

with :

```bash
DEBUG=0
```

Then run:

```bash
./run-tests.sh
```

## Run tests over each WordPress versions

```bash
./run-tests-with-all-wp-versions.sh
```