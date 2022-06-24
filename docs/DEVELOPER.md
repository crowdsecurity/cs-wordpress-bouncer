![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec WordPress Bouncer

## Developer guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Local development](#local-development)
  - [DDEV-Local setup](#ddev-local-setup)
    - [DDEV installation](#ddev-installation)
  - [Prepare DDEV WordPress environment](#prepare-ddev-wordpress-environment)
  - [WordPress installation](#wordpress-installation)
  - [DDEV Usage](#ddev-usage)
    - [Test the module](#test-the-module)
      - [Install the module](#install-the-module)
      - [End-to-end tests](#end-to-end-tests)
    - [Update composer dependencies](#update-composer-dependencies)
      - [Development phase](#development-phase)
      - [Production release](#production-release)
- [Quick start guide](#quick-start-guide)
  - [Live mode](#live-mode)
    - [Discover the cache system](#discover-the-cache-system)
    - [Try "ban" remediation](#try-ban-remediation)
    - [Try "captcha" remediation](#try-captcha-remediation)
  - [Stream mode, for the high traffic websites](#stream-mode-for-the-high-traffic-websites)
  - [Try Redis or Memcached](#try-redis-or-memcached)
    - [Redis](#redis)
    - [Memcached](#memcached)
- [Commit message](#commit-message)
  - [Allowed message `type` values](#allowed-message-type-values)
- [Release process](#release-process)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Local development

There are many ways to install this plugin on a local WordPress environment.

We are using [DDEV-Local](https://ddev.readthedocs.io/en/stable/) because it is quite simple to use and customize.

Of course, you may use your own local stack, but we provide here some useful tools that depends on DDEV.


### DDEV-Local setup

For a quick start, follow the below steps.

__We will suppose here that you want to install WordPress 5.9. Please change "5.9" depending on your needs__


#### DDEV installation

This project is fully compatible with DDEV 1.19.3, and it is recommended to use this specific version.
For the DDEV installation, please follow the [official instructions](https://ddev.readthedocs.io/en/stable/#installation).
On a Linux distribution, you can run:
```
sudo apt-get -qq update
sudo apt-get -qq -y install libnss3-tools
curl -LO https://raw.githubusercontent.com/drud/ddev/master/scripts/install_ddev.sh
bash install_ddev.sh v1.19.3
rm install_ddev.sh
```

### Prepare DDEV WordPress environment

The final structure of the project will look like below.

```
wp-sources (choose the name you want for this folder)
│
│ (WordPress sources)
│
└───.ddev (do not change this folder name)
│   │
│   │ (Cloned sources of a specific WordPress ddev repo)
│
└───my-own-modules (do not change this folder name)
    │
    │
    └───crowdsec-bouncer (do not change this folder name)
       │
       │ (Sources of a this module)

```

- Create an empty folder that will contain all necessary sources:
```
mkdir wp-sources
```
- Create an empty `.ddev` folder for DDEV and clone our pre-configured DDEV repo:

```
mkdir wp-sources/.ddev && cd wp-sources/.ddev && git clone git@github.com:julienloizelet/ddev-wp.git ./
```
- Copy some configurations file:

```
cp .ddev/config_overrides/config.wp59.yaml .ddev/config.wp59.yaml
cp .ddev/config_overrides/config.crowdsec.yaml .ddev/config.crowdsec.yaml
```
- Launch DDEV

```
cd .ddev && ddev start
```
This should take some times on the first launch as this will download all necessary docker images.


### WordPress installation

```
cd wp-sources
wget https://wordpress.org/wordpress-5.9.tar.gz
tar -xf wordpress-5.9.tar.gz wordpress
cp -r wordpress/. ./
rm -rf wordpress
rm wordpress-5.9.tar.gz
ddev start
ddev exec wp core install --url='https://wp59.ddev.site' --title='WordPress' --admin_user='admin'
--admin_password='admin123' --admin_email='admin@admin.com'

```


### DDEV Usage

#### Test the module

##### Install the module

```
cd wp-sources
mkdir my-own-modules &&  mkdir my-own-modules/crowdsec-bouncer && cd my-own-modules/crowdsec-bouncer
git clone git@github.com:crowdsecurity/cs-wordpress-bouncer.git ./
cd wp-sources
cp .ddev/additional_docker_compose/docker-compose.crowdsec.yaml .ddev/docker-compose.crowdsec.yaml
cp .ddev/additional_docker_compose/docker-compose.playwright.yaml .ddev/docker-compose.playwright.yaml
ddev start
```

Login to the admin by browsing the url `https://wp59.ddev.site/admin` (username: `admin` and password: `admin123`)

Activate the CrowdSec plugin

##### End-to-end tests

```
cd wp-sources/my-own-module/crowdsec-bouncer/tests/e2e-ddev/__scripts__
```
Ensure that `run-tests.sh` and `test-init.sh` files are executable. Run `chmod +x run-tests.sh test-init.sh` if not.


Before testing with the `docker` or `ci` parameter, you have to install all the required dependencies
in the playwright container with this command :

    ./test-init.sh

If you want to test with the `host` parameter, you will have to install manually all the required dependencies:

```
yarn --cwd ./tests/e2e-ddev --force
yarn global add cross-env
```

Finally, you can test by running:

`./run-tests.sh [context] [files]` where `[context]` can be `ci`, `docker` or `host` and files is the list of file to
test (all files if empty);

For example:
```
./run-tests.sh host "./2-live-mode-remediations.js"
```

#### Update composer dependencies

As WordPress plugins does not support `composer` installation, we have to add the vendor folder to sources. By doing
that, we have to use only production ready dependencies and avoid `require-dev` parts. We have also set a config
platform version of PHP in the `composer.json` that will force composer to install packages on this specific version.
We are not setting the `"optimize-autoloader": true` in the `composer.json` because it implies a lot of issues during
development phase.

##### Development phase

In development phase, you could run the following command:

```
ddev composer update --working-dir ./my-own-modules/crowdsec-bouncer
```

##### Production release

To release a new version of the plugin on the WordPress marketplace, you must run:

```
ddev composer update --no-dev --prefer-dist --optimize-autoloader --working-dir ./my-own-modules/crowdsec-bouncer
```

## Quick start guide

This guide exposes you the main features of the plugin.

Before all, please retrieve your host IP (a.k.a. <YOUR_HOST_IP>)with the command:

`ddev find-ip`

And then, as ddev use a router, you need to set the router IP in the CrowdSec plugin trusted IPs.

To find this IP, just run:

`ddev find-ip ddev-router` and save this value in the `Trust these CDN IPs
(or Load Balancer, HTTP Proxy)` field of the `Advanced` CrowdSec plugin tab.


### Live mode

We will start using "live" mode. You'll understand what it is after try the stream mode.

* In wp-admin, ensure the bouncer is configured with **live** mode (stream mode disabled).

#### Discover the cache system

* In a browser tab, visit the public home of your local WordPress site. You're allowed because LAPI said your IP is clean.

> To avoid latencies when the clean IP browse the website, the bouncer will keep this information in cache for 30 
> seconds (you can change this value in the avdanced settings page). In other words, LAPI will not be requested to 
> check this IP for the next 30 seconds.

* If you want to skip this delay, feel free to clear the cache in the wp-admin.


#### Try "ban" remediation

* In a terminal, ban your own IP for 4 hours:

```bash

# Ban your own IP for 4 hours:
ddev exec -s crowdsec cscli decisions add --ip <YOUR_HOST_IP> --duration 4h --type ban
```

* Immediately, the public home is now locked with a short message to explain you that you are banned.


#### Try "captcha" remediation


* Now, request captcha for your own IP for 15m:

```bash

# Clear all existing decisions
ddev exec -s crowdsec cscli decisions delete --all

# Add a captcha
ddev exec -s exec crowdsec cscli decisions add --ip <YOUR_HOST_IP> --duration 15m --type captcha
```

* The public home now request you to fill a captcha.

* Unless you manage to solve the captcha, you'll not be able to access the website.

> Note: when you resolve the captcha in your browser, the associated PHP session is considered as sure.
> If you remove the captcha decision with `cscli`, then you add a new captcha decision for your IP, you'll not be prompted for the current PHP session. To view the captcha page, You can force using a new PHP session opening the front page with incognito mode.

### Stream mode, for the high traffic websites

With live mode, as you tried it just before, each time a user arrives to the website for the first time, a call is made to LAPI. If the traffic on your website is high, the bouncer will call LAPI very often.

To avoid this, LAPI offers a "stream" mode. The decisions list is updated at a predefined frequency and kept in cache. Let's try it!

> This bouncer uses the WordPress cron system. For demo purposes, we encourage you to install the WP-Control plugin, a plugin to view and control each Wordpress Cron task jobs.

First, clear the previous decisions:

```bash
# Clear all existing decisions
ddev exec -s exec crowdsec cscli decisions delete --all
```

* Then enable "stream" mode and set the resynchronisation frequency to 30 seconds. If you installed WP-Control plugin, 
  you can 
  see that a new cron task has just been added here `http://your-wordpress-url/wp-admin/tools.php?
  page=crontrol_admin_manage_page`.

* As the whole blocklist has just been loaded in cache (0 decision!), your IP is allowed. The public home is available.

* Now, if you ban your IP for 4h:

```bash
ddev exec -s crowdsec cscli decisions add --ip <YOUR_HOST_IP> --duration 4h --type ban
```

* In less than 30 seconds your IP will be banned and the public home will be locked.

Conclusion: with the stream mode, LAPI decisions are fetched on a regular basis rather than being called when user arrives for the first time.

### Try Redis or Memcached

In order to get better performances, you can switch the cache technology.

The docker-compose file started 2 unused containers, redis and memcached.

#### Redis

- Just go to the advanced settings page.
- select the **Caching technology** named "Redis" and
- type `redis://redis:6379` in the "Redis DSN" field.

#### Memcached

- select the **Caching technology** named "Memcached" and
- type `memcached://memcached:11211` in the "Memcached DSN" field.



## Commit message

In order to have an explicit commit history, we are using some commits message convention with the following format:

    <type>(<scope>): <subject>

Allowed `type` are defined below.
`scope` value intends to clarify which part of the code has been modified. It can be empty or `*` if the change is a
global or difficult to assign to a specific part.
`subject` describes what has been done using the imperative, present tense.

Example:

    feat(admin): Add css for admin actions


You can use the `commit-msg` git hook that you will find in the `.githooks` folder :

```
cp .githooks/commit-msg .git/hooks/commit-msg
chmod +x .git/hooks/commit-msg
```

### Allowed message `type` values

- chore (automatic tasks; no production code change)
- ci (updating continuous integration process; no production code change)
- comment (commenting;no production code change)
- docs (changes to the documentation)
- feat (new feature for the user)
- fix (bug fix for the user)
- refactor (refactoring production code)
- style (formatting; no production code change)
- test (adding missing tests, refactoring tests; no production code change)


## Release process

We are using [semantic versioning](https://semver.org/) to determine a version number. To verify the current tag,
you should run:
```
git describe --tags `git rev-list --tags --max-count=1`
```

Before publishing a new release, there are some manual steps to take:

- Change the version number and stable tag in the `crowdsec.php` file
- Change the stable tag  in the `readme.txt` file
- Change the version number in the `inc/constants.php` file
- Update the `CHANGELOG.md` file

Then, you have to [run the action manually from the GitHub repository](https://github.com/crowdsecurity/cs-wordpress-bouncer/actions/workflows/release.yml)


Alternatively, you could use the [Github CLI](https://github.com/cli/cli):
- create a draft release:
```
gh workflow run release.yml -f tag_name=vx.y.z -f draft=true
```
- publish a prerelease:
```
gh workflow run release.yml -f tag_name=vx.y.z -f prerelease=true
```
- publish a release:
```
gh workflow run release.yml -f tag_name=vx.y.z
```

Note that the Github action will fail if the tag `tag_name` already exits.




