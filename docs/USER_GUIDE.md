![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec WordPress Bouncer

## User Guide

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Description](#description)
- [Prerequisites](#prerequisites)
- [Usage](#usage)
  - [Features](#features)
  - [Configurations](#configurations)
    - [General settings](#general-settings)
    - [Theme customization](#theme-customization)
    - [Advanced settings](#advanced-settings)
  - [Auto Prepend File mode](#auto-prepend-file-mode)
    - [PHP](#php)
    - [Nginx](#nginx)
    - [Apache](#apache)
- [Resources](#resources)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Description

The `CrowdSec Bouncer` plugin for WordPress has been designed to protect WordPress websites from all kinds of 
attacks by using [CrowdSec](https://crowdsec.net/) technology.

## Prerequisites

To be able to use this bouncer, the first step is to install [CrowdSec v1](https://doc.crowdsec.net/docs/getting_started/install_crowdsec/).
CrowdSec is only in charge of the "detection", and won't block anything on its own. You need to deploy a bouncer to "apply" decisions.

Please note that first and foremost CrowdSec must be installed on a server that is accessible via the WordPress site.


## Usage

### Features

When a user is suspected by CrowdSec to be malevolent, this bouncer will either send him/her a captcha to resolve or
simply a page notifying that access is denied. If the user is considered as a clean user, he will access the page as normal.

By default, the ban wall is displayed as below:

![Ban wall](images/screenshots/front-ban.jpg)

By default, the captcha wall is displayed as below:

![Captcha wall](images/screenshots/front-captcha.jpg)

Please note that it is possible to customize all the colors of these pages in a few clicks so that they integrate best with your design.

On the other hand, all texts are also fully customizable. This will allow you, for example, to present translated pages in your users’ language.


### Configurations

This plugin comes with configurations that you will find under `CrowdSec` admin section.

These configurations are divided in three main parts : `CrowdSec`, `Theme customization`,and `Advanced`.

#### General settings

In the `CrowdSec` part, you will set your connection details and refine bouncing according to your needs.

![Connection details](images/screenshots/config-connection.jpg)

***

`Connection details → LAPI URL`

Url to join your CrowdSec LAPI.

***

`Connection details → Bouncer API key`

Key generated by the cscli command.

***

`Bouncing → Bouncing level`

Choose if you want to apply CrowdSec directives (`Normal bouncing`) or be more permissive (`Flex bouncing`).

With the `Flex mode`, it is impossible to accidentally block access to your site to people who don’t deserve it. This
mode makes it possible to never ban an IP but only to offer a Captcha, in the worst-case scenario.

***

`Bouncing → Public website only`

If enabled, the admin is not bounced.

***


#### Theme customization

In the `Theme customization` part, you can modify texts and colors of ban and captcha walls.

![Captcha wall customization](images/screenshots/config-captcha.jpg)

![Ban wall customization](images/screenshots/config-ban.jpg)

![Wall CSS](images/screenshots/config-css.jpg)


#### Advanced settings

In the `Advanced` part, you can enable/disable the stream mode, choose your cache system for your CrowdSec
LAPI, handle your remediation policy and adjust some debug and log parameters.

![Communication mode](images/screenshots/config-communication-mode.jpg)


***

`Communication mode to the API → Enable the "Stream mode"`

Choose if you want to enable the `stream mode` or stay in `live mode`.


By default, the `live mode` is enabled. The first time a stranger connects to your website, this mode means that the IP will be checked directly by the CrowdSec API. The rest of your user’s browsing will be even more transparent thanks to the fully customizable cache system.

But you can also activate the `stream mode`. This mode allows you to constantly feed the bouncer with the malicious IP list via a background task (CRON), making it to be even faster when checking the IP of your visitors. Besides, if your site has a lot of unique visitors at the same time, this will not influence the traffic to the API of your CrowdSec instance.

***

`Communication mode to the API → Resync decisions each (stream mode only)`

With the stream mode, every decision is retrieved in an asynchronous way. Here you can define the frequency of this
cache refresh.

**N.B** : There is also a refresh button if you want to refresh the cache manually.

***

![Cache](images/screenshots/config-cache.jpg)

***

`Caching configuration → Technology`

Choose the cache technology that will use your CrowdSec LAPI.

The File system cache is faster than calling LAPI. Redis or Memcached is faster than the File System cache.

**N.B** : There are also a `Clear now` button fo all cache technologies and a `Prune now` button dedicated to the
file system cache.

***

`Caching configuration → Recheck clean IPs each (live mode only)`

The duration between re-asking LAPI about an already checked clean IP.

Minimum 1 second.  Note that this setting can not be apply in stream mode.

***

`Caching configuration → Recheck bad IPs each (live mode only)`

The duration between re-asking LAPI about an already checked bad IP.

Minimum 1 second.  Note that this setting can not be apply in stream mode.


***


![Remediations](images/screenshots/config-remediations.jpg)

***

`Remediations → Fallback to`

Choose which remediation to apply when CrowdSec advises unhandled remediation.

***

`Remediations → Trust these CDN IPs (or Load Balancer, HTTP Proxy)`

If you use a CDN, a reverse proxy or a load balancer, it is possible to indicate in the bouncer settings the IP ranges of these devices in order to be able to check the IP of your users. For other IPs, the bouncer will not trust the X-Forwarded-For header.
***

`Remediations → Hide CrowdSec mentions`

Enable if you want to hide CrowdSec mentions on the Ban and Captcha walls.

![Debug](images/screenshots/config-debug.jpg)

***

`Debug mode → Enable debug mode`

Enable if you want to see some debug information in a specific log file.

***

`Display errors → Enable errors display`

When this mode is enabled, you will see every unexpected bouncing errors in the browser.
Should be disabled in production.


### Auto Prepend File mode

By default, this extension will bounce every web requests: e.g requests called from webroot `index.php`.
This implies that if another php public script is called (`cron.php` if accessible for example, or any of your
custom public php script) bouncing will not be effective.
To ensure that any php script will be bounced if called from a browser, you should try the `auto prepend file` mode.

In this mode, every browser access to a php script will be bounced.

To enable the `auto prepend file` mode, you have to configure your server by adding an `auto_prepend_file` directive 
for your php setup.

**N.B:**
- In this mode, a setting file `inc/standalone-settings.php` will be generated each time you save the 
  CrowdSec plugin configuration from the WordPress admin.


Adding an `auto_prepend_file` directive can be done in different ways:

#### PHP

You should add this line to a `.ini` file :

    auto_prepend_file = /wordpress-root-directory/wp-content/plugins/cs-wordpress-bouncer/inc/standalone-bounce.php


#### Nginx


If you are using Nginx, you should modify your Magento 2 nginx configuration file by adding a `fastcgi_param`
directive. The php block should look like below:

```
location ~ \.php$ {
    ...
    ...
    ...
    fastcgi_param PHP_VALUE "/wordpress-root-directory/wp-content/plugins/cs-wordpress-bouncer/inc/standalone-bounce.php";
}
```

#### Apache

If you are using Apache, you should add this line to your `.htaccess` file:

    php_value auto_prepend_file "/wordpress-root-directory/wp-content/plugins/cs-wordpress-bouncer/inc/standalone-bounce.php"



## Resources

Feel free to look at the [associated article](https://crowdsec.net/wordpress-bouncer/) for more configuration options and tweaks.