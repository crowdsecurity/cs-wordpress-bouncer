# Install the wordpress and the plugin locally using docker-compose

Follow this guide to get the development stack installed locally.

## Install `composer` dependencies

```bash
docker-compose exec wordpress composer install --working-dir /var/www/html/wp-content/plugins/cs-wordpress-bouncer --prefer-source
```

> In this dev environment, we use `--prefer-source` to be able to develop the bouncer library at the same time. Composer will may ask you for your own Github token to download sources instead of using dist packages.


## [Linux host only] Fix permissions for docker volumes when using a Linux host:

To allow container to create directories, please fix the permissions to:

```sh
chmod 777 . logs
```

> Note: Do this only in local development context. We'll try to find a better solution.

## Install the stack for development purpose

Select the PHP version you want to use (7.2, 7.3, 7.4, 8.0) :

```bash
export CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2
```

## Install e2e tests dependencies to automatically configure Wordpress and the CrowdSec plugin

* In order to run the excellent [Playwright](https://playwright.dev/) tool, you need to have `node` and `yarn` installed.

You have also have to install the required dependencies to run e2e tests with:

```bash
cd tests/e2e && yarn && cd -
```

Now there is two options:

## A) Automatic comfiguration

Install Wordpress instance and activate plugin through the e2e tests:

You can do this automatically with:

```bash
SETUP_ONLY=1 DEBUG=1 ./tests-local.sh
```

## B) Manual comfiguration

Alternatively, you can install wordpress and the plugin manually with:

```bash
docker-compose up -d wordpress crowdsec mysql redis memcached
```

Then visit the wordpress instance here: http://localhost:8050 and install the wordpress instance.

Infos to setup the plugin:

To get a bouncer key:

```bash
docker-compose exec crowdsec cscli bouncers add wordpress-bouncer
```

The LAPI URL is:

http://crowdsec:8080