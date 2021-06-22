# Install the wordpress and the plugin locally using docker-compose

Follow this guide to get the development stack installed locally.

> Prerequises:
>
> -   you should have [`docker`](https://docs.docker.com/get-docker/) installed
> -   `docker` should be [`runnable without sudo`](https://docs.docker.com/engine/install/linux-postinstall/).
> -   [`docker-compose`](https://docs.docker.com/compose/install/) installed locally.
> -   IPv6 should be enable in your docker configuration. Enabled it following [this guide](https://docs.docker.com/config/daemon/ipv6/). (Note that you'll may have to create the file `/etc/docker/daemon.json`).
> -   If your develop environnment is MacOS, please refer to the [MacOS host installation guide](macos-host.md).

## Install the stack for development purpose

Before all, create a `.env` file, using:

```bash
cp .env.example .env
```

> Note about PHP 8.0: WordPress official docker image [does not officially supports PHP 8.0](https://hub.docker.com/_/wordpress?tab=tags&page=1&ordering=last_updated) at this time. However, as the CrowdSec PHP Library does support PHP 8.0, there is a good chance that the plugin will work fine with PHP 8.0, but we can not currently test it.

## Configure WordPress and the CrowdSec Plugin

Now there are two options, you can fill the Wordpress installation wizard manually OR use let the e2e tests to do it for you.

### A) Automatic configuration

Install Wordpress instance and activate plugin launching the e2e tests (limited to the installation steps):

```bash
SETUP_ONLY=1 ./run-tests.sh
```

### B) Manual comfiguration

Alternatively, you can install wordpress and the plugin manually with:

```bash
docker-compose up -d wordpress crowdsec mysql redis memcached
```

Then visit the wordpress instance here: http://localhost and install the wordpress instance.

Infos to setup the plugin:

To get a bouncer key:

```bash
docker-compose exec crowdsec cscli bouncers add wordpress-bouncer
```

The LAPI URL is:

http://crowdsec:8080

## Try the plugin behavior

| Info            | Value                                |
| --------------- | ------------------------------------ |
| Public blog URL | http://localhost                     |
| Blog admin URL  | http://localhost/wp-admin            |
| Admin username  | `admin`                              |
| Pasword         | `my_very_very_secret_admin_password` |
