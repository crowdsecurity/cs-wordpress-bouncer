# Run e2e tests

* Before all, be sure to [get the stack installed using the docker-compose guide](install-with-docker-compose.md).

> Note: If you have some problems while running tests, `docker system prune --volumes` can help.

Headless mode (faster way):

```bash
./tests-local.sh
```

Debug mode (display the browser window and slow down speed. To use when adding new tests):

```bash
DEBUG=1 ./tests-local.sh
```

> Note: you can add `await jestPlaywright.debug()` when you want to pause the process.

All the versions:

```bash
./tests-local-wpall.sh
```