# Install the wordpress and the plugin locally using docker-compose

Follow this guide to get the development stack installed locally.

## Install the stack for development purpose

Select the PHP version you want to use (7.2, 7.3, 7.4, 8.0) :

```bash
export CS_WORDPRESS_BOUNCER_PHP_VERSION=7.2
```

## Configure WordPress and the CrowdSec Plugin

Now there is two options:

### A) Automatic comfiguration

Install Wordpress instance and activate plugin through the e2e tests:

You can do this automatically with:

```bash
SETUP_ONLY=1 DEBUG=1 ./tests-local.sh
```

### B) Manual comfiguration

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
