![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec PHP remediation engine

## User Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Description](#description)
- [Features](#features)
- [Quick start](#quick-start)
  - [Installation](#installation)
  - [Capi Remediation](#capi-remediation)
    - [Instantiation](#instantiation)
    - [Features](#features-1)
      - [Retrieve fresh decisions from CAPI](#retrieve-fresh-decisions-from-capi)
      - [Get remediation for an IP](#get-remediation-for-an-ip)
      - [Clear cache](#clear-cache)
      - [Prune cache](#prune-cache)
    - [Stream mode and example scripts](#stream-mode-and-example-scripts)
  - [Lapi Remediation](#lapi-remediation)
    - [Instantiation](#instantiation-1)
    - [Features](#features-2)
      - [Get Decisions stream list from LAPI](#get-decisions-stream-list-from-lapi)
      - [Get remediation for an IP](#get-remediation-for-an-ip-1)
      - [Clear cache](#clear-cache-1)
      - [Prune cache](#prune-cache-1)
    - [Example scripts](#example-scripts)
      - [Get decisions stream](#get-decisions-stream)
      - [Get remediation for an IP](#get-remediation-for-an-ip-2)
      - [Get remediation for an IP using geolocation](#get-remediation-for-an-ip-using-geolocation)
      - [Clear cache](#clear-cache-2)
      - [Prune cache](#prune-cache-2)
- [CAPI remediation engine configurations](#capi-remediation-engine-configurations)
  - [Remediation priorities](#remediation-priorities)
  - [Remediation fallback](#remediation-fallback)
  - [Geolocation](#geolocation)
  - [Refresh frequency indicator](#refresh-frequency-indicator)
- [LAPI remediation engine configurations](#lapi-remediation-engine-configurations)
  - [Stream mode](#stream-mode)
  - [Clean IP cache duration](#clean-ip-cache-duration)
  - [Bad IP cache duration](#bad-ip-cache-duration)
- [Cache configurations](#cache-configurations)
  - [PhpFiles cache files directory](#phpfiles-cache-files-directory)
  - [Redis cache DSN](#redis-cache-dsn)
  - [Memcached cache DSN](#memcached-cache-dsn)
  - [Cache tags](#cache-tags)
- [Helpers](#helpers)
  - [Origins count](#origins-count)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Description

The main purpose of this library is to determine what action to take for a given IP.

This kind of action is called a remediation and can be: 

- a `bypass`: in case there is no associated CrowdSec decision for the IP (i.e. this is a clean IP).
- any of available CrowdSec decision types : `ban`, `captcha` and other custom types.


## Features

- CrowdSec remediations
  - Retrieve and cache decisions from CAPI or LAPI
    - Handle IP scoped decisions for Ipv4 and IPv6
    - Handle Range scoped decisions for IPv4
    - Handle Country scoped decisions using [MaxMind](https://www.maxmind.com) database
    - Handle List decisions
  - Determine remediation for a given IP
    - Use the cached decisions for CAPI and for LAPI in stream mode
    - For LAPI in live mode, call LAPI if there is no cached decision
    - Use customizable remediation priorities
  
- Overridable cache handler (built-in support for `Redis`, `Memcached` and `PhpFiles` caches)


- Large PHP matrix compatibility: 7.2, 7.3, 7.4, 8.0, 8.1, 8.2 and 8.3


## Quick start

### Installation

First, install CrowdSec PHP remediation engine via the [composer](https://getcomposer.org/) package manager:
```bash
composer require crowdsec/remediation-engine
```

Please see the [Installation Guide](./INSTALLATION_GUIDE.md) for more details.

### Capi Remediation

To retrieve decisions from CAPI and determine which remediation should apply to an IP, we use the 
`CapiRemediation` class.

#### Instantiation

To instantiate a `CapiRemediation` object, you have to:

- Pass its `configs` array as a first parameter. You will find below [the list of other available
  settings](#capi-remediation-engine-configurations).


- Pass a CrowdSec CAPI Watcher client as a second parameter. Please see [CrowdSec CAPI PHP client](https://github.com/crowdsecurity/php-capi-client) for details.


- Pass an implementation of the provided `CacheStorage\AbstractCache` in the third parameter.  You will find 
  examples of such implementation with the `CacheStorage\PhpFiles`,  `CacheStorage\Memcached` and `CacheStorage\Redis` 
  class.


- Optionally, to log some information, you can pass an implementation of the `Psr\Log\LoggerInterface` as a fourth
    parameter. You will find an example of such implementation with the provided `CrowdSec\CommonLogger\FileLog` class.



```php
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\CapiClient\Watcher;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CapiRemediation;
use CrowdSec\Common\Logger\FileLog;

// Init logger
$logger = new FileLog(['debug_mode' => true]);
// Init client
$clientConfigs = [
    'machine_id_prefix' => 'remediationtest',
    'scenarios' => ['crowdsecurity/http-sensitive-files'],
];
$capiClient = new Watcher($clientConfigs, new FileStorage(), null, $logger);
// Init PhpFiles cache storage
$cacheConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache',
];
$phpFileCache = new PhpFiles($cacheConfigs, $logger);
// Init CAPI remediation
$remediationConfigs = [];
$remediationEngine = new CapiRemediation($remediationConfigs, $capiClient, $phpFileCache, $logger);
```
#### Features

Once your CAPI remediation engine is instantiated, you can perform the following calls:


##### Retrieve fresh decisions from CAPI

```php
$remediationEngine->refreshDecisions();
```

This method will use the CrowdSec CAPI client (`$capiClient`) to retrieve arrays of new and deleted decisions 
from CAPI. Then, new decisions will be cached using the `CacheStorage` implementation (`$phpFileCache` here) and 
deleted ones will be removed if necessary.

Practically, you should use some cron job to refresh decisions every 2 to 12 hours, 4h recommended.


##### Get remediation for an IP

```php
$ip = ...;// Could be the current user IP
$remediationEngine->getIpRemediation($ip);
```

This method will ask the `CacheStorage` to know if there are any decisions matching the IP in cache. If there is no 
cached decision, a `bypass` will be returned. If there are one or more decisions, the decision type with the highest priority will be returned.


##### Clear cache

```php
$remediationEngine->clearCache();
```

This method will delete all the cached items.

##### Prune cache

```php
$remediationEngine->pruneCache();
```

Unlike Memcached and Redis, there is no PhpFiles pruning mechanism that automatically removes expired items.
Thus, if you are using the PhpFiles cache, you should use this method.


#### Stream mode and example scripts

The CAPI remediation engine is intended to work asynchronously: this is what we call the `stream mode`: 

1) CAPI decisions should be retrieved via a background task (CRON) and stored in cache.

2) To retrieve a remediation for an IP, we are asking the cache and not CAPI directly.



- For the first point, you should create a php script that will be called by a cron task.

You will find an example of such a script with the `tests/scripts/refresh-decisions-capi.php` file.

As we recommend to ask CAPI every 2 hours for fresh decisions, you may have to use this kind of crontab configuration: 

```
0 */2 * * * www-data /usr/bin/php /path/to/refresh-decisions-capi.php
```

- For the second point, you should have look to the `tests/scripts/get-remediation-capi.php` example.


- Depending on your need, you could also have to clear or prune the cache (by CRON or on demand). You will find two 
example scripts for that : `tests/scripts/clear-cache-capi.php` and `tests/scripts/prune-cache-capi.php`.


### Lapi Remediation

To retrieve decisions from LAPI and determine which remediation should apply to an IP, we use the
`LapiRemediation` class.

#### Instantiation

To instantiate a `LapiRemediation` object, you have to:

- Pass its `configs` array as a first parameter. You will find below [the list of other available
  settings](#lapi-remediation-engine-configurations).


- Pass a CrowdSec LAPI Bouncer client as a second parameter. Please see [CrowdSec LAPI PHP client](https://github.com/crowdsecurity/php-lapi-client) for details.


- Pass an implementation of the provided `CacheStorage\AbstractCache` in the third parameter.  You will find
  examples of such implementation with the `CacheStorage\PhpFiles`,  `CacheStorage\Memcached` and `CacheStorage\Redis`
  class.


- Optionally, to log some information, you can pass an implementation of the `Psr\Log\LoggerInterface` as a fourth
  parameter. You will find an example of such implementation with the provided `CrowdSec\Common\Logger\FileLog` class.


```php
use CrowdSec\CapiClient\Storage\FileStorage;
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\Common\Logger\FileLog;

// Init logger
$logger = new FileLog(['debug_mode' => true]);
// Init client
$clientConfigs = [
    'auth_type' => 'api_key',
    'api_url' => 'http://your-lapi-url:8080',
    'api_key' => '****************',
];
$lapiClient = new Bouncer($clientConfigs, null, $logger);
// Init PhpFiles cache storage
$cacheConfigs = [
    'fs_cache_path' => __DIR__ . '/.cache',
];
$phpFileCache = new PhpFiles($cacheConfigs, $logger);
// Init LAPI remediation
$remediationConfigs = [];
$remediationEngine = new LapiRemediation($remediationConfigs, $lapiClient, $phpFileCache, $logger);
```

#### Features

Once your LAPI remediation engine is instantiated, you can perform the following calls:


##### Get Decisions stream list from LAPI


```php
$remediationEngine->refreshDecisions();
```

LAPI allows to pass `$startup` and `$filter` parameters when retrieving streamed decisions. Please see the [CrowdSec 
LAPI documentation](https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/bouncers/getDecisionsStream) for more details.


- The `refreshDecisions` will use a `warm_up` cached item to detect if this is a first call (`$startup=true`) or a 
  decisions update (`$startup=false`): 

  - If there is no `warm_up` cached item, the `$startup` flag is set to true, all the decisions are returned and the 
    `warm_up` item is cached. Furthermore, cache will be cleaned before retrieving decisions of this first call.
  - If there is a `warm_up` cached item, the `$startup` flag is set to false and only the decisions updates (add or 
    remove) from the last stream call are returned.


- The second parameter `$filter` will be the array `['scopes'=>'ip,range']` by default and `['scopes'=>'ip,range, country']` if geolocation feature is enabled (see [Geolocation configuration](#geolocation)).

##### Get remediation for an IP

```php
$ip = ...;// Could be the current user IP
$remediationEngine->getIpRemediation($ip);
```

This method will ask the `CacheStorage` to know if there are any decisions matching the IP in cache. 

Then, process depends on the `stream_mode` configuration: 

- In stream mode, if there is no cached decision, a `bypass` will be returned.
- In live mode, if there is no cached decision, direct call to LAPI will be done to retrieve and cache decisions 
  related to the IP.


Finally, if there are one or more decisions, the decision type with the highest priority will be returned.


##### Clear cache

```php
$remediationEngine->clearCache();
```

This method will delete all the cached items.

##### Prune cache

```php
$remediationEngine->pruneCache();
```

Unlike Memcached and Redis, there is no PhpFiles pruning mechanism that automatically removes expired items.
Thus, if you are using the PhpFiles cache, you should use this method.

#### Example scripts

You will find some ready-to-use php scripts in the `tests/scripts` folder. These scripts could be useful to better
understand what you can do with this remediation engine.


##### Get decisions stream

###### Command usage

```php
php tests/scripts/refresh-decisions-lapi.php  <BOUNCER_KEY> <LAPI_URL>
```

###### Example usage

```bash
php tests/scripts/refresh-decisions-lapi.php 68c2b479830c89bfd48926f9d764da39  https://crowdsec:8080 
```

##### Get remediation for an IP

###### Command usage

```php
php tests/scripts/get-remediation-lapi.php <IP> <BOUNCER_KEY> <LAPI_URL> <STREAM_MODE>
```

###### Example usage

```bash
php tests/scripts/get-remediation-lapi.php 1.2.3.4 0b85479f39a8152af8b27b316ad0a80c  https://crowdsec:8080 0
```


##### Get remediation for an IP using geolocation

This test require to have at least one Maxmind database (`GeoLite2-Country.mmdb`) in the `tests/geolocation` folder. 
These database is downloadable from the [MaxMind](https://www.maxmind.com) website.

###### Command usage

```php
php tests/scripts/get-remediation-lapi-with-geoloc.php <IP> <BOUNCER_KEY> <LAPI_URL> <STREAM_MODE>
```

###### Example usage

```bash
php tests/scripts/get-remediation-lapi-with-geoloc.php 1.2.3.4 0b85479f39a8152af8b27b316ad0a80c  https://crowdsec:8080 0
```


##### Clear cache

###### Command usage

```php
php tests/scripts/clear-cache-lapi.php <BOUNCER_KEY> <LAPI_URL>
```

###### Example usage

```bash
php tests/scripts/clear-cache-lapi.php c580ebdff45da6e01415ed0e9bc9c06b  https://crowdsec:8080
```

##### Prune cache

###### Command usage

```php
php tests/scripts/prune-cache-lapi.php <BOUNCER_KEY> <LAPI_URL>
```

###### Example usage

```bash
php tests/scripts/prune-cache-lapi.php c580ebdff45da6e01415ed0e9bc9c06b  https://crowdsec:8080
```


## CAPI remediation engine configurations

The first parameter `$configs` of the `CapiRemediation` constructor can be used to pass the following settings:

### Remediation priorities

```php
$configs = [
        ... 
        'ordered_remediations' => ['ban', 'captcha']
        ...
];
```

The `ordered_remediations` setting accepts an array of remediations ordered by priority. 

If there are more than one decision for an IP, remediation with the highest priority will be return.

The specific remediation `bypass` will always be considered as the lowest priority (there is no need to specify it 
in this setting).

This setting is not required. If you don't set any value, `['ban']` will be used by default for CAPI remediation and
`['ban', 'captcha']` for LAPI remediation.


In the example above, priorities can be summarized as `ban > captcha > bypass`.


### Remediation fallback

```php
$configs = [
        ... 
        'fallback_remediation' => 'ban'
        ...
];
```

The `fallback_remediation` setting will be used to determine which remediation to use in case a decision has a 
type that does not belong to the `ordered_remediations` setting.

This setting is not required. If you don't set any value, `bypass` will be used by default.

If you set some value, be aware to include this value in the `ordered_remediations` setting too.

In the example above, if a retrieved decision has the unknown `mfa` type, the `ban` fallback will be use instead.


### Geolocation

```php
$configs = [
        ... 
        'geolocation' => [
            'enabled' => true,
            'cache_duration' => 86400,
            'type' => 'maxmind',
            'maxmind' => [
                'database_type' => 'country',
                'database_path' => '/var/www/html/geolocation/GeoLite2-Country.mmdb',
            ],
        ]
        ...
];
```

This setting is not required.

- `geolocation[enabled]`: `true` to enable remediation based on country. Default to `false`.


- `geolocation[type]`:  Geolocation system. Only `maxmind` is available for the moment. Default to `maxmind`.


- `geolocation[cache_duration]`: This setting will be used to set the lifetime (in seconds) of a cached country 
  associated to an IP. The purpose is to avoid multiple call to the geolocation system (e.g. maxmind database). Default to 86400. Set 0 to disable caching.


- `geolocation[maxmind][database_type]`: Select from `country` or `city`. These are the two available MaxMind 
  database types. Default to `country`.


- `geolocation[maxmind][database_path]`: Absolute path to the MaxMind database (e.g. mmdb file)

### Refresh frequency indicator

When your CAPI watcher client is subscribed to a blocklist, it retrieves decisions from a certain block list url 
during the decisions refresh call. We will use this `refresh_frequency_indicator`setting to optimize how to pull such 
list decisions.

You must use a number that represents the frequency (in seconds) of your cron job. For example, if you pull 
decisions every 2 hours, you would set `7200`.


This setting is not required. If you don't set any value, `14400` (4h) will be used by default.


## LAPI remediation engine configurations

The first parameter `$configs` of the `LapiRemediation` constructor can be used to pass some settings.

As for the CAPI remediation engine above, you can pass `ordered_remediations`, `fallback_remediation` and 
`geolocation` settings.

In addition, LAPI remediation engine handles the following settings:

### Stream mode

```php
$configs = [
        ... 
        'stream_mode' => false
        ...
];
```

`true` to enable stream mode, `false` to enable the live mode. Default to `true`. 

The stream mode allows you to constantly feed the cache with the 
malicious IP list via a background task (CRON), making it to be even faster when checking the IP of your visitors. Besides, if your site has a lot of unique visitors at the same time, this will not influence the traffic to the API of your CrowdSec instance.


In live mode, the first time you try to get remediation for an IP, a direct call to the CrowdSec LAPI will be done. 
Decisions will be cached with a lifetime depending on the `clean_ip_cache_duration` and `bad_ip_cache_duration` 
settings below.


### Clean IP cache duration

```php
$configs = [
        ... 
        'clean_ip_cache_duration' => 120
        ...
];
```

If there is no decision for an IP, this IP will be considered as "clean" and this setting will be used to set the 
cache lifetime of the `bypass` remediation to store. 

This is only useful in live mode. In stream mode, a "clean" IP is considered as "clean" until the next resynchronisation.

In seconds. Must be greater or equal than 1. Default to 60 seconds if not set.

### Bad IP cache duration

```php
$configs = [
        ... 
        'bad_ip_cache_duration' => 86400
        ...
];
```

If there is an active decision for an IP, this IP will be considered as "bad" and this setting will be used to set the
cache lifetime of the remediation to store (`ban`, `captcha`, etc.). More specifically, the lifetime will be the 
minimum between this setting and the decision duration.

This is only useful in live mode. In stream mode, the cache duration depends only on the decision duration. 

In seconds. Must be greater or equal than 1. Default to 120 seconds if not set.


## Cache configurations

If you use one of our provided cache storage handler (`PhpFiles`,  `Memcached` or 
`Redis`), you will need to pass a `$cacheConfigs` array as first parameter:
### PhpFiles cache files directory

```php
$cacheConfigs = [
        ... 
        'fs_cache_path' => __DIR__ . '/.cache'
        ...
];
```

This setting is required and cannot be empty.

### Redis cache DSN

```php
$cacheConfigs = [
        ... 
        'redis_dsn' => 'redis://localhost:6379'
        ...
];
```

This setting is required and cannot be empty.

### Memcached cache DSN

```php
$cacheConfigs = [
        ... 
        'memcached_dsn' => 'memcached://localhost:11211'
        ...
];
```

This setting is required and cannot be empty.

### Cache tags

If you are using the provided PhpFiles or Redis cache, you may want to use the [Symfony cache tags invalidation 
feature](https://symfony.com/doc/current/components/cache/cache_invalidation.html#using-cache-tags). In order to instantiate a tag aware adapter, you need to pass the value `true` for the setting `use_cache_tags`.

Example:

```php
$cacheConfigs = [
        ... 
        'fs_cache_path' => __DIR__ . '/.cache',
        'use_cache_tags' => true
        ...
];
```

This setting is not required and is `false` by default.

Beware that there is a caveat with Symfony tagged caching and Redis: it doesn't support the max memory policy set to `allkeys-lru`. You need to change this to `noeviction` or `volatile-*` instead; otherwise the caching won't work at all.

Cache tags is not supported for the provided Memcached cache.


## Helpers

### Origins count

In order to have some metrics, we store in cache the number of calls to the `getIpRemedation` method while 
separating the counters by origin of the final remediation. 

The `getOriginsCount` helper method returns an array whose keys are origins and values are the counter associated to 
the origin. When the remediation is a `bypass` (i.e. no active decision for the tested IP), we set the origin as 
`clean`.

```php
/** @var $remediation \CrowdSec\RemediationEngine\AbstractRemediation */
$originsCount = $remediation->getOriginsCount();

/*$originsCount = [
    'clean' => 150, 
    'capi' => 28,
    'lists' => 16
]*/
```
