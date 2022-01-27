# DDEV-Local stack for WordPress

The purpose of this repo is to share my WordPress [DDEV-Local](https://ddev.readthedocs.io/en/stable/) stack.


<!-- START doctoc generated TOC please keep comment here to allow auto update -->

<!-- END doctoc generated TOC please keep comment here to allow auto update -->

## Quick start

_We will suppose that you want to test on a WordPress 5.6.5 instance. Change the version number if you prefer another
release._

### DDEV-Local installation

Please follow the [official instructions](https://ddev.readthedocs.io/en/stable/#installation). On a Linux
distribution, this should be as simple as

    sudo apt-get install linuxbrew-wrapper
    brew tap drud/ddev && brew install ddev


### Prepare DDEV WordPress environment

The final structure of the project will look like below.

```
wp-sources
│
│ (WordPress sources)
│
└───.ddev
│   │
│   │ (Cloned sources of a specific WordPress ddev repo)
│
└───my-own-modules
    │
    │
    └───crowdsec-bouncer
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
cp .ddev/config_overrides/config.wp565.yaml .ddev/config.wp565.yaml
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
wget https://wordpress.org/wordpress-5.6.5.tar.gz
tar -xf wordpress-5.6.5.tar.gz wordpress
cp -r wordpress/. ./
rm -rf wordpress
rm wordpress-5.6.5.tar.gz
ddev start
ddev exec wp core install --url='https://wp565.ddev.site' --title='WordPress' --admin_user='admin'
--admin_password='admin123' --admin_email='admin@admin.com'

```


## Usage

### Test the module

#### Install the module

```
cd wp-sources
mkdir my-own-modules &&  mkdir my-own-modules/crowdsec-bouncer && cd my-own-modules/crowdsec-bouncer
git clone git@github.com:crowdsecurity/cs-wordpress-bouncer.git ./
ddev composer install --working-dir ./my-own-modules/crowdsec-bouncer
cd wp-sources
cp .ddev/additional_docker_compose/docker-compose.crowdsec.yaml .ddev/docker-compose.crowdsec.yaml
cp .ddev/additional_docker_compose/docker-compose.playwright.yaml .ddev/docker-compose.playwright.yaml
ddev start
```

Login to the admin by browsing the url `https://wp565.ddev.site/admin` (username: `admin` and password: `admin123`)

Activate the CrowdSec plugin

#### End-to-end tests

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

`./run-tests.sh host "./2-live-mode-remediations.js"`

## License

[MIT](LICENSE)
