![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec WordPress Bouncer

## Developer guide

**Table of Contents**
<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->

- [Local development](#local-development)
  - [DDEV setup](#ddev-setup)
    - [DDEV installation](#ddev-installation)
  - [Prepare DDEV WordPress environment](#prepare-ddev-wordpress-environment)
  - [WordPress installation](#wordpress-installation)
  - [DDEV Usage](#ddev-usage)
    - [Test the module](#test-the-module)
    - [Update composer dependencies](#update-composer-dependencies)
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
- [Update documentation table of contents](#update-documentation-table-of-contents)
- [Release process](#release-process)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Local development

There are many ways to install this plugin on a local WordPress environment.

We are using [DDEV](https://ddev.readthedocs.io/en/stable/) because it is quite simple to use and customize.

Of course, you may use your own local stack, but we provide here some useful tools that depends on DDEV.


### DDEV setup

For a quick start, follow the below steps.

__We will suppose here that you want to install WordPress 5.9. Please change "5.9" depending on your needs__


#### DDEV installation

This project is fully compatible with DDEV 1.21.4, and it is recommended to use this specific version. For the DDEV installation, please follow the [official instructions](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/).

### Prepare DDEV WordPress environment

The final structure of the project will look like below.

```
wp-sources (choose the name you want for this folder)
│
│ (WordPress sources)
│
└───.ddev
│   │
│   │ (DDEV files)
│
└───wp-content/plugins
    │
    │
    └───crowdsec (do not change this folder name)
       │
       │ (Sources of a this module)

```

- Create an empty folder that will contain all necessary sources:
```shell
mkdir wp-sources && cd wp-sources
```
- Create a DDEV WordPress project with some DDEV add-ons

```shell
ddev config --project-type=wordpress --project-name=your-project-name
ddev get ddev/ddev-redis
ddev get ddev/ddev-memcached
ddev get julienloizelet/ddev-tools
ddev get julienloizelet/ddev-playwright
```

If you wish to use an older version of WordPress, add

`define( 'AUTOMATIC_UPDATER_DISABLED', true );` in the `wp-config-ddev.php` file.

- Launch DDEV

```shell
ddev start
```
This should take some times on the first launch as this will download all necessary docker images.


### WordPress installation

```
ddev wp core download

ddev exec wp core install --url='https://your-project-name.ddev.site' --title='WordPress' --admin_user='admin' --admin_password='admin123' --admin_email='admin@admin.com'

```


### DDEV Usage

#### Test the module

##### Install the module

```shell
mkdir -p wp-content/plugins/crowdsec && cd wp-content/plugins/crowdsec

git clone git@github.com:crowdsecurity/cs-wordpress-bouncer.git ./
```


Login to the admin by browsing the url `https://your-project-name.ddev.site/admin` (username: `admin` and password: `admin123`)

Activate the CrowdSec plugin.

Add some Crowdsec tools and restart:

```
ddev get julienloizelet/ddev-crowdsec-php
ddev restart
```

##### End-to-end tests

We are using a Jest/Playwright Node.js stack to launch a suite of end-to-end tests.

**Please note** that those tests modify local configurations and log content on the fly.

As we use a TLS ready CrowdSec container, you have first to copy some certificates and key:

```bash
cd wp-sources
mkdir -p crowdsec/tls
cp -r .ddev/okaeli-add-on/custom_files/crowdsec/cfssl/* crowdsec/tls
```

For geolocation test, you have to put city and country MaxMind databases in a specific folder

```
mkdir -p crowdsec/geolocation
ddev maxmind-download DEFAULT GeoLite2-City crowdsec/geolocation
ddev maxmind-download DEFAULT GeoLite2-Country crowdsec/geolocation
cd crowdsec/geolocation
sha256sum -c GeoLite2-Country.tar.gz.sha256.txt
sha256sum -c GeoLite2-City.tar.gz.sha256.txt
tar -xf GeoLite2-Country.tar.gz
tar -xf GeoLite2-City.tar.gz
```

For AppSec post request test, we are using a custom page. You have to create this page in your WordPress site: 

```bash
cd wp-sources
cat .ddev/okaeli-add-on/wordpress/custom_files/crowdsec/html/appsec-post.html | ddev wp post create --post_type=page --post_status=publish --post_title="AppSec" -   
```  



And we use also a custom PHP script to make some cache test. Thus, you should copy this PHP script too in the root folder: 

```bash
cd wp-sources
cp  .ddev/okaeli-add-on/wordpress/custom_files/crowdsec/php/cache-actions-with-wordpress-load.php cache-actions.php 
```


Then, ensure that `run-tests.sh` and `test-init.sh` files are executable.

```shell
cd wp-sources/wp-content/plugins/crowdsec/tests/e2e-ddev/__scripts__
```
Run `chmod +x run-tests.sh test-init.sh` if not.

Then you can use the `run-test.sh` script to run the tests:

- the first parameter specifies if you want to run the test on your machine (`host`) or in the
  docker containers (`docker`). You can also use `ci` if you want to have the same behavior as in GitHub action.
- the second parameter list the test files you want to execute. If empty, all the test suite will be launched.

In other words, you can test by running:

`./run-tests.sh [context] [files]` where `[context]` can be `ci`, `docker` or `host` and files is the list of file to
test (all files if empty);

For example:
```
./run-tests.sh host "./2-live-mode-remediations.js"
```

###### Test in docker

Before testing with the `docker` or `ci` parameter, you have to install all the required dependencies
in the playwright container with this command :

    ./test-init.sh

###### Test on host


If you want to test with the `host` parameter, you will have to install manually all the required dependencies:

```
yarn --cwd ./tests/e2e-ddev --force
yarn global add cross-env
```

You will also have to edit your `/etc/hosts` file to add the following line:

```
<crowdec-container-ip> crowdsec
```
where `<crowdec-container-ip>` is the IP of the `crowdsec` container. You can find it with the command `ddev find-ip crowdsec`.

Example:

```
172.19.0.5     crowdsec
```

##### Testing timeout in the CrowdSec container

If you need to test a timeout, you can use the following command:

Install `iproute2`
```bash
ddev exec -s crowdsec apk add iproute2
```
Add the delay you want:
```bash
ddev exec -s crowdsec tc qdisc add dev eth0 root netem delay 500ms
```

To remove the delay:
```bash
ddev exec -s crowdsec tc qdisc del dev eth0 root netem
```




##### Auto_prepend_file mode

To enable the `auto_prepend_file` mode, you can use this command:

```bash
cd wp-sources/.ddev
ddev nginx-config okaeli-add-on/wordpress/custom_files/crowdsec/crowdsec-prepend-nginx-site.conf
```



#### Update composer dependencies

As WordPress plugins does not support `composer` installation, we have to add the vendor folder to sources. By doing that, we have to use only production ready dependencies and avoid `require-dev` parts. We have also set a `config` platform version of PHP in the `composer.json` that will force composer to install packages on this specific version.

We are not setting the `"optimize-autoloader": true` in the `composer.json` because it implies a lot of issues during development phase.

##### Development phase

In development phase, you could run the following command:

```shell
ddev composer update --working-dir ./wp-content/plugins/crowdsec
```

##### Production release

To release a new version of the plugin on the WordPress marketplace, you must run:

```shell
ddev composer update --no-dev --prefer-dist --optimize-autoloader --working-dir ./wp-content/plugins/crowdsec
```

## Quick start guide

This guide exposes you the main features of the plugin.

Before all, please retrieve your host IP (a.k.a. <YOUR_HOST_IP>) with the command:

`ddev find-ip`

And then, as ddev use a router, you need to set the router IP in the CrowdSec plugin trusted IPs.

To find this IP, just run:

`ddev find-ip ddev-router` and save this value in the `Trust these CDN IPs
(or Load Balancer, HTTP Proxy)` field of the `Advanced` CrowdSec plugin tab.


### Live mode

We will start using "live" mode. You'll understand what it is after try the stream mode.

* In wp-admin, ensure the bouncer is configured with **live** mode (stream mode disabled).

#### Discover the cache system

* In a browser tab, visit the public home of your local WordPress site. You're allowed because Local API said your IP is clean.

> To avoid latencies when the clean IP browse the website, the bouncer will keep this information in cache for 30 
> seconds (you can change this value in the avdanced settings page). In other words, Local API will not be requested to 
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

> Note: when you resolve the captcha in your browser, the result is stored in cache. If you remove the captcha decision with `cscli`, then you add a new captcha decision for your IP, you'll not be prompted until you clear the cache or the lifetime for captcha decision has been reached.

### Stream mode, for the high traffic websites

With live mode, as you tried it just before, each time a user arrives to the website for the first time, a call is made to Local API. If the traffic on your website is high, the bouncer will call Local API very often.

To avoid this, Local API offers a "stream" mode. The decisions list is updated at a predefined frequency and kept in cache.

> This bouncer uses the WordPress cron system. For demo purposes, we encourage you to install the WP-Control plugin, a plugin to view and control each WordPress Cron task jobs.

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

Conclusion: with the stream mode, Local API decisions are fetched on a regular basis rather than being called when user arrives for the first time.

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

`scope` value intends to clarify which part of the code has been modified. It can be empty or `*` if the change is a global or difficult to assign to a specific part.

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


## Update documentation table of contents

To update the table of contents in the documentation, you can use [the `doctoc` tool](https://github.com/thlorenz/doctoc).

First, install it:

```bash
npm install -g doctoc
```

Then, run it in the documentation folder:

```bash
doctoc docs/*
```



## Release process

We are using [semantic versioning](https://semver.org/) to determine a version number. To verify the current tag,
you should run:
```
git describe --tags `git rev-list --tags --max-count=1`
```

Before publishing a new release, there are some manual steps to take:

- Change the version number and stable tag in the `crowdsec.php` file
- Change the stable tag  in the `readme.txt` file
- Change the version number in the `inc/Constants.php` file
- Update the `CHANGELOG.md` file

Then, you have to [run the action manually from the GitHub repository](https://github.com/crowdsecurity/cs-wordpress-bouncer/actions/workflows/release.yml)


Alternatively, you could use the [GitHub CLI](https://github.com/cli/cli):
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

Note that the GitHub action will fail if the tag `tag_name` already exits.
