![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec LAPI PHP client

## Developer guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Local development](#local-development)
  - [DDEV setup](#ddev-setup)
    - [DDEV installation](#ddev-installation)
    - [Prepare DDEV PHP environment](#prepare-ddev-php-environment)
  - [DDEV Usage](#ddev-usage)
    - [Use composer to update or install the lib](#use-composer-to-update-or-install-the-lib)
    - [Unit test](#unit-test)
    - [Integration test](#integration-test)
    - [Coding standards](#coding-standards)
    - [Testing timeout in the CrowdSec container](#testing-timeout-in-the-crowdsec-container)
- [Commit message](#commit-message)
  - [Allowed message `type` values](#allowed-message-type-values)
- [Update documentation table of contents](#update-documentation-table-of-contents)
- [Release process](#release-process)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->



## Local development

There are many ways to install this library on a local PHP environment.

We are using [DDEV](https://ddev.readthedocs.io/en/stable/) because it is quite simple to use and customize.

Of course, you may use your own local stack, but we provide here some useful tools that depends on DDEV.


### DDEV setup

For a quick start, follow the below steps.


#### DDEV installation

This project is fully compatible with DDEV 1.21.4, and it is recommended to use this specific version.
For the DDEV installation, please follow the [official instructions](https://ddev.readthedocs.io/en/stable/users/install/ddev-installation/).

#### Prepare DDEV PHP environment

The final structure of the project will look like below.

```
crowdsec-lapi-dev-project (choose the name you want for this folder)
│       
│
└───.ddev
│   │   
│   │ (DDEV files)
│   
└───my-code (do not change this folder name)
    │   
    │
    └───lapi-client (do not change this folder name)
       │   
       │ (Clone of this repo)
         
```

- Create an empty folder that will contain all necessary sources:
```bash
mkdir crowdsec-lapi-dev-project
```

- Create a DDEV php project:

```bash
cd crowdsec-lapi-dev-project
ddev config --project-type=php --php-version=8.2 --project-name=crowdsec-lapi-client
```

- Add some DDEV add-ons:

```bash
ddev get julienloizelet/ddev-tools
ddev get julienloizelet/ddev-crowdsec-php
```

- Clone this repo sources in a `my-code/lapi-client` folder:

```bash
mkdir -p my-code/lapi-client
cd my-code/lapi-client && git clone git@github.com:crowdsecurity/php-lapi-client.git ./
```


### DDEV Usage


#### Use composer to update or install the lib

Run:

```bash
ddev composer update --working-dir ./my-code/lapi-client
```

#### Unit test

```bash
ddev php ./my-code/lapi-client/vendor/bin/phpunit  ./my-code/lapi-client/tests/Unit --testdox
```

#### Integration test

First, create a bouncer and keep the result key.

```bash
ddev create-bouncer
```

Then, as we use a TLS ready CrowdSec container, you have to copy some certificates and key:

```bash
cd crowdsec-lapi-dev-project
mkdir cfssl
cp -r .ddev/okaeli-add-on/custom_files/crowdsec/cfssl/* cfssl
```

Finally, run

In order to launch integration tests, we have to set some environment variables:

```bash
ddev exec BOUNCER_KEY=<BOUNCER_KEY> AGENT_TLS_PATH=/var/www/html/cfssl APPSEC_URL=http://crowdsec:7422 LAPI_URL=https://crowdsec:8080 php ./my-code/lapi-client/vendor/bin/phpunit  ./my-code/lapi-client/tests/Integration --testdox --exclude-group timeout     
```

`<BOUNCER_KEY>` should have been created and retrieved before this test by running `ddev create-bouncer`.

If you need to test with a TLS authentication, you should launch:

```bash
ddev exec BOUNCER_TLS_PATH=/var/www/html/cfssl BOUNCER_KEY=<BOUNCER_KEY> AGENT_TLS_PATH=/var/www/html/cfssl 
APPSEC_URL=http://crowdsec:7422 LAPI_URL=https://crowdsec:8080 php ./my-code/lapi-client/vendor/bin/phpunit  ./my-code/lapi-client/tests/Integration --testdox --exclude-group timeout     
```

#### Coding standards

We set up some coding standards tools that you will find in the `tools/coding-standards` folder.
In order to use these, you will need to work with a PHP version >= 7.4 and run first:

```bash
ddev composer update --working-dir=./my-code/lapi-client/tools/coding-standards
```

##### PHPCS Fixer

We are using the [PHP Coding Standards Fixer](https://cs.symfony.com/)

With ddev, you can do the following:


```bash
ddev phpcsfixer my-code/lapi-client/tools/coding-standards/php-cs-fixer ../
```

##### PHPSTAN

To use the [PHPSTAN](https://github.com/phpstan/phpstan) tool, you can run:


```bash
ddev phpstan /var/www/html/my-code/lapi-client/tools/coding-standards phpstan/phpstan.neon /var/www/html/my-code/lapi-client/src

```


##### PHP Mess Detector

To use the [PHPMD](https://github.com/phpmd/phpmd) tool, you can run:

```bash
ddev phpmd ./my-code/lapi-client/tools/coding-standards phpmd/rulesets.xml ../../src

```

##### PHPCS and PHPCBF

To use [PHP Code Sniffer](https://github.com/squizlabs/PHP_CodeSniffer) tools, you can run:

```bash
ddev phpcs ./my-code/lapi-client/tools/coding-standards my-code/lapi-client/src PSR12
```

and:

```bash
ddev phpcbf  ./my-code/lapi-client/tools/coding-standards my-code/lapi-client/src PSR12
```


##### PSALM

To use [PSALM](https://github.com/vimeo/psalm) tools, you can run:

```bash
ddev psalm ./my-code/lapi-client/tools/coding-standards ./my-code/lapi-client/tools/coding-standards/psalm
```

##### PHP Unit Code coverage

In order to generate a code coverage report, you have to:


- Enable `xdebug`:
```bash
ddev xdebug
```

To generate a html report, you can run:
```bash
ddev php -dxdebug.mode=coverage ./my-code/lapi-client/tools/coding-standards/vendor/bin/phpunit --configuration ./my-code/lapi-client/tools/coding-standards/phpunit/phpunit.xml
```

You should find the main report file `dashboard.html` in `tools/coding-standards/phpunit/code-coverage` folder.


If you want to generate a text report in the same folder:

```bash
ddev php -dxdebug.mode=coverage ./my-code/lapi-client/tools/coding-standards/vendor/bin/phpunit --configuration ./my-code/lapi-client/tools/coding-standards/phpunit/phpunit.xml --coverage-text=./my-code/lapi-client/tools/coding-standards/phpunit/code-coverage/report.txt
```

#### Testing timeout in the CrowdSec container

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

To execute integration tests with a timeout, you can run:

```bash
ddev exec BOUNCER_KEY=<BOUNCER_KEY> AGENT_TLS_PATH=/var/www/html/cfssl APPSEC_URL=http://crowdsec:7422 
LAPI_URL=https://crowdsec:8080 php ./my-code/lapi-client/vendor/bin/phpunit  ./my-code/lapi-client/tests/Integration --testdox --group timeout     
```



## Commit message

In order to have an explicit commit history, we are using some commits message convention with the following format:

    <type>(<scope>): <subject>

Allowed `type` are defined below.
`scope` value intends to clarify which part of the code has been modified. It can be empty or `*` if the change is a
global or difficult to assign to a specific part.
`subject` describes what has been done using the imperative, present tense.

Example:

    feat(bouncer): Add a new endpoint for bouncer


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
doctoc docs/* --maxlevel 4  
```


## Release process

We are using [semantic versioning](https://semver.org/) to determine a version number. 

Before publishing a new release, there are some manual steps to take:

- Change the version number in the `Constants.php` file
- Update the `CHANGELOG.md` file

Then, you have to [run the action manually from the GitHub repository](https://github.com/crowdsecurity/php-lapi-client/actions/workflows/release.yml)


Alternatively, you could use the [GitHub CLI](https://github.com/cli/cli) to publish a release: 

```
gh workflow run release.yml -f tag_name=vx.y.z
```

Note that the GitHub action will fail if the tag `tag_name` already exits.


 
