![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec CAPI PHP client

## User Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Description](#description)
- [Features](#features)
- [Quick start](#quick-start)
  - [Installation](#installation)
  - [Watcher instantiation](#watcher-instantiation)
    - [CAPI calls](#capi-calls)
      - [Push signals](#push-signals)
      - [Get Decisions stream list](#get-decisions-stream-list)
      - [Enroll a watcher](#enroll-a-watcher)
- [Watcher configurations](#watcher-configurations)
  - [Environment](#environment)
  - [Machine Id prefix](#machine-id-prefix)
  - [User Agent suffix](#user-agent-suffix)
  - [User Agent version](#user-agent-version)
  - [Scenarios](#scenarios)
  - [CAPI timeout](#capi-timeout)
- [Storage implementation](#storage-implementation)
- [Override the curl request handler](#override-the-curl-request-handler)
  - [Custom implementation](#custom-implementation)
  - [Ready to use `file_get_contents` implementation](#ready-to-use-file_get_contents-implementation)
- [Example scripts](#example-scripts)
  - [Get decisions stream](#get-decisions-stream)
    - [Command usage](#command-usage)
    - [Example usage](#example-usage)
  - [Push signals](#push-signals-1)
    - [Command usage](#command-usage-1)
    - [Example](#example)
  - [Enroll a watcher](#enroll-a-watcher-1)
    - [Command usage](#command-usage-2)
    - [Example](#example-1)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Description

This client allows you to interact with the CrowdSec Central API (CAPI).

## Features

- CrowdSec CAPI Watcher available endpoints
  - Push signals
  - Retrieve decisions stream list
  - Enroll a watcher
- Automatic management of watcher credentials (password, machine_id and login token)
- Overridable request handler (`curl` by default, `file_get_contents` also available)


## Quick start

### Installation

First, install CrowdSec CAPI PHP Client via the [composer](https://getcomposer.org/) package manager:
```bash
composer require crowdsec/capi-client
```

Please see the [Installation Guide](./INSTALLATION_GUIDE.md) for mor details.

### Watcher instantiation

To instantiate a watcher, you have to:


- Pass its `scenarios` in a `configs` array as a first parameter. You will find below [the list of other available 
  settings](#watcher-configurations).


- Pass an implementation of the provided `StorageInterface` in the second parameter. For this quick start, we will 
  use a basic `FileStorage` implementation, but we advise you to develop a more secured class as we are storing sensitive data.


- Optionally, you can pass an implementation of the `AbstractRequestHandler` (from the `crowdsec/common` dependency package) as a third parameter. By default, a `Curl` request handler will be used.


- Optionally, to log some information, you can pass an implementation of the `Psr\Log\LoggerInterface` as a fourth
  parameter. You will find an example of such implementation with the provided `Logger\FileLog` class of the
  `crowdsec/common` dependency package.

```php
use CrowdSec\CapiClient\Watcher;
use Crowdsec\CapiClient\Storage\FileStorage;

$configs = ['scenarios' => ['crowdsecurity/http-backdoors-attempts']];
$storage = new FileStorage();
$client = new Watcher($configs, $storage);
````

By default, a watcher will use the CrowdSec development environment. If you are ready to use the CrowdSec production 
environment, you have to add the key `env` with value `prod` in the `$configs` array: 
```php
$configs = [
        'scenarios' => ['crowdsecurity/http-backdoors-attempts'], 
        'env' => 'prod'
];
$client = new WatcherClient($configs, $storage);
```

#### CAPI calls

Once your watcher is instantiated, you can perform the following calls:


##### Push signals

You can push an array of signals to CAPI:

```php
/**
* @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals for fields details
 */
$signals = ['...'];
$client->pushSignals($signals);
```

In order to quickly create a well formatted signal, we provide two helper methods: `buildSimpleSignalForIp` and 
`buildSignal`:


###### Simple signal builder 

The method `buildSimpleSignalForIp` will return a signal reflecting a ban type alert for the given IP.

```php
$signal1 = $client->buildSimpleSignalForIp('1.2.3.4', 'crowdsecurity/http-bad-user-agent', null);
$signal2 = $client->buildSimpleSignalForIp(
    '5.6.7.8', 
    'crowdsecurity/http-bad-user-agent', 
    new \DateTime('2022-12-24 14:55:30'),
    '6 events over 30s',
    86400
);
$client->pushSignals([$signal1, $signal2]);
```

Available parameters for this method are: 

- `$ip` : The IP associated to the alert. This is required.

- `$scenario` : The scenario that triggered the alert. This is required.

- `$createdAt`: The creation date of the alert. This is required : must implement DateTimeInterface or be null. If
  null, the current time will be used.

- `$message` : A human-readable message to add context for the alert. This is not required. Default to an empty message.

- `$duration`: The time to live (in seconds) of the decision. This is not required. Default to 86400.


###### Advanced signal builder

If you want to create a signal with more detailed data, you could use the `buildSignal` method.

```php
$properties = [
  'scenario' => 'crowdsecurity/http-bad-user-agent'
  'created_at' => new \DateTime('2023-01-03 12:56:36', new \DateTimeZone('UTC'));
  'message' => '6 events over 30s',
  'start_at' => new \DateTime('2023-01-03 12:56:05', new \DateTimeZone('UTC'));
  'stop_at' => new \DateTime('2023-01-03 12:56:35', new \DateTimeZone('UTC'));
];

$source = [
  'scope' => 'range'
  'value' =>  '1.2.3.4/24'
];

$decisions = [
  [
  'id' => 0
  'duration' => 3600
  'scenario' => 'crowdsecurity/http-bad-user-agent'
  'origin' => 'crowdsec'
  'scope' => 'range'
  'value' => '1.2.3.4/24'
  'type' => 'ban'
  ]
];

$signal = $client->buildSignal($properties, $source, $decisions);

$client->pushSignals([$signal]);
```

You have to pass 3 arrays as parameters for this method: 


- An array `$properties` with the following available keys: 
  - `scenario`: The scenario that triggered the alert. This is required.
  - `created_at`: The creation date of the alert. This is required : must implement DateTimeInterface or be null. If 
    null, the current time will be used.
  - `message`: A human-readable message to add context for the alert. This is not required. Default to an empty message.
  - `start_at`: First event date for alert. This is not required. Default to `created_at` value.
  - `stop_at`: Last event date for alert. This is not required. Default to `created_at` value.


- An array `$source` with the following available keys:
  - `scope`: The scope of the alert : `ip`, `range`, etc. This is not required. Default to `ip`.
  - `value`: It depends on the scope : it could be an IP (if scope is `ip`), a range (if scope is `range`) or
    any value that matches with the current scope. This is required.


- An array `$decisions` that could be empty or contains decision arrays with the 
  following available keys:
  - `id`: The decision id (integer) if known, 0 otherwise. This is not required. Default to 0.
  - `duration`: The time to live (in seconds) of the decision. This is not required. Default to 86400.
  - `scenario`: This is not required. Default to the `$properties` `scenario` value.
  - `origin`: Origin of the decision. This is not required. Default to "crowdsec".
  - `scope`: This is not required. Default to the `$source` `scope` value.
  - `value` => This is not required. Default to the `$source` `value` value.
  - `type` => Decision type: `ban`, `captcha` or any custom remediation. This is not required. Default to `ban`.


##### Get Decisions stream list

To retrieve the list of top decisions, you can do the following call:

```php
$client->getStreamDecisions();
```

##### Enroll a watcher

To enroll a watcher you have to specify:

- The `name` that will be display in the console for the instance
- An `overwrite` boolean to force enroll the instance or not
- An `enrollKey` that is generated in your CrowdSec backoffice account (a.k.a. `enrollement key`)
- Optionally, an array of `tags` to apply on the console for the instance


```php
$client->enroll('MyWatcher', true, '*****************', ['my_tag']);
```

## Watcher configurations

The first parameter `$configs` of the Watcher constructor can be used to pass the following settings:

### Environment

```php
$configs = [
        ... 
        'env' => 'prod'
        ...
];
```

The `env` setting only accepts two values : `dev` and `prod`. 

This setting is not required. If you don't set any value, `dev` will be used by default.

It will mainly change the called CAPI url:
- `https://api.dev.crowdsec.net/v2/` for the `dev` environment
- `https://api.crowdsec.net/v2/` for the `prod` one.

You should also use it in your own code to implement different behaviors depending on the environment. For example, the `FileStorage` class accepts a second parameter `$env` in its constructor to manage distinct `dev` and `prod`credential files.

### Machine Id prefix


```php
$configs = [
        ... 
        'machine_id_prefix' => 'mycustomwatcher'
        ...
];
```

This setting is not required.

When you make your first call with a watcher, a `machine_id` will be generated and stored through your storage 
implementation. This `machine_id` is a string of length 48 composed of characters matching the regular expression `#^[a-z0-9]+$#`.

The `machine_id_prefix` setting allows to set a custom prefix to this `machine_id`. It must be a string matching the regular expression `#^[a-z0-9]{0,16}$#`. 

The final generated `machine_id` will still have a length of 48.

Beware that changing `machine_id_prefix` between two watcher instantiations may imply a new `machine_id\password` 
pair generation and registration.


### User Agent suffix

```php
$configs = [
        ... 
        'user_agent_suffix' => 'MySuffix'
        ...
];
```
This setting is not required.

Sending a `User-Agent` header during a CAPI call is mandatory. By default, user agent will be `csphpcapi/vX.Y.Z` where 
`vX.Y.Z` is the current release version of this library.

You can add a custom suffix to this value by using the `user_agent_suffix` setting.  It must be a string matching the regular expression `#^[a-z0-9]{0,16}$#`.

With the example setting above, result will be  `csphpcapi_MySuffix/vX.Y.Z`.


### User Agent version

```php
$configs = [
        ... 
        'user_agent_version' => 'v2.3.0'
        ...
];
```
This setting is not required.

As mentioned above, default user agent is `csphpcapi/vX.Y.Z` where `vX.Y.Z` is the current release version of this 
library.

You can add a custom version to this value by using the `user_agent_version` setting. It must be a string matching the regular expression `#^v\d{1,4}(\.\d{1,4}){2}$#`.

With the example setting above, result will be  `csphpcapi/v2.3.0`.


### Scenarios

```php
$configs = [
        ... 
        'scenarios' => ['crowdsecurity/http-backdoors-attempts', 'crowdsecurity/http-bad-user-agent']
        ...
];
```

This `scenarios` setting is required.

You have to pass an array of CrowdSec scenarios that will be used to log in your watcher. 
You should find a list of available scenarios on the [CrowdSec hub collections page](https://hub.crowdsec.net/browse/).


Each scenario must match the regular expression `#^[A-Za-z0-9]{0,16}\/[A-Za-z0-9_-]{0,32}$#`.


### CAPI timeout


```php
$configs = [
        ... 
        'api_timeout' => 10
        ...
];
```

This setting is not required.

This is the maximum number of seconds allowed to execute a CAPI request.

It must be an integer. If you don't set any value, default value is 120. If you set a negative value, timeout is 
unlimited. 


## Storage implementation

The purpose of the `Storage/StorageInterface.php` interface is to give a guide on how to store and retrieve all 
required data for interact with CAPI as a watcher.

Note that you have to implement 8 methods : 

- `retrieveMachineId`: Returns the stored `machine_id` or `null` if not found.

- `retrievePassword`: Returns the stored `password` or `null` if not found.

- `retrieveScenarios`: Returns the stored array of `scenarios` or `null` if not found.

- `retrieveToken`: Returns the stored `token` or `null` if not found.

- `storeMachineId`: Stores a `machine_id` in your storage. Returns `true` on success and `false` otherwise.

- `storePassword`: Stores a `password` in your storage. Returns `true` on success and `false` otherwise.

- `storeScenarios`: Stores a `scenarios` array in your storage. Returns `true` on success and `false` otherwise.

- `storeToken`: Stores a `token` array in your storage. Returns `true` on success and `false` otherwise.

As an example, you should look on the `Storage/FileStorage.php` class that stores and retrieves data from some
files on your filesystem.

Beware that this example is not secure enough as we are talking here about sensitive data like `password`, `token`
and `machine_id`.


## Override the curl request handler

### Custom implementation

By default, the `Watcher` object will do curl requests to call the CAPI. If for some reason, you don't want to 
use curl then you can create your own request handler class and pass it as a second parameter of the `Watcher` 
constructor. 

Your custom request handler class must extend the `AbstractRequestHandler` class of the `crowdsec/common` dependency,
and you will have to explicitly write an `handle` method:

```php
<?php

use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler;

class CustomRequestHandler extends AbstractRequestHandler
{
    /**
     * Performs an HTTP request and returns a response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        /**
        * Make your own implementation of an HTTP request process.
        * Request object contains a URI, a method, headers (optional) and parameters (optional).
        * Response object contains a json body, a status code and headers (optional).
        */
    }
}
```

Once you have your custom request handler, you can instantiate the watcher that will use it:

```php
use CrowdSec\CapiClient\Watcher;
use CustomRequestHandler;

$requestHandler = new CustomRequestHandler();

$client = new Watcher($configs, $storage, $requestHandler);
```

Then, you can make any of the CAPI calls that we have seen above.


### Ready to use `file_get_contents` implementation

This client comes with a `file_get_contents` request handler that you can use instead of the default curl request 
handler. To use it, you should instantiate it and pass the created object as a parameter: 

```php
use CrowdSec\CapiClient\Watcher;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;

$requestHandler = new FileGetContents($configs);

$client = new Watcher($configs, $storage, $requestHandler);
```

**N.B.**: Please note that you should pass a `$configs` param if you want to use some configuration value as 
`api_timeout`. 


## Example scripts


You will find some ready-to-use php scripts in the `tests/scripts` folder. These scripts could be useful to better 
understand what you can do with this client. 

As Watcher methods need at least an array as parameter, we use a json format in command line.


### Get decisions stream

#### Command usage

```php
php tests/scripts/watcher/decisions-stream.php <SCENARIOS_JSON>
```

#### Example usage

```bash
php tests/scripts/watcher/decisions-stream.php '["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]'
```

Or, with the `file_get_contents` handler:

```bash
php tests/scripts/watcher/request-handler-override/decisions-stream.php '["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]'
```

### Push signals

#### Command usage

```php
php tests/scripts/watcher/signals.php <SCENARIOS_JSON> <SIGNALS_JSON>
```

#### Example

```bash
php tests/scripts/watcher/signals.php '["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]' '[{"message":"Ip 1.1.1.1 performed crowdsecurity/http-path-traversal-probing (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338","scenario":"crowdsecurity/http-path-traversal-probing","scenario_hash":"","scenario_version":"","source":{"id":1,"as_name":"TEST","cn":"FR","ip":"1.1.1.1","latitude":48.9917,"longitude":1.9097,"range":"1.1.1.1\/32","scope":"Ip","value":"1.1.1.1"},"start_at":"2020-11-06T20:13:41.196817737Z","stop_at":"2020-11-06T20:14:11.189252228Z"},{"message":"Ip 2.2.2.2 performed crowdsecurity/http-probing (6 events over 29.992437958s) at 2020-11-06 20:14:11.189255784 +0000 UTC m=+52.785061338","scenario":"crowdsecurity/http-probing","scenario_hash":"","scenario_version":"","source":{"id":2,"as_name":"TEST","cn":"FR","ip":"2.2.2.2","latitude":48.9917,"longitude":1.9097,"range":"2.2.2.2\/32","scope":"Ip","value":"2.2.2.2"},"start_at":"2020-11-06T20:13:41.196817737Z","stop_at":"2020-11-06T20:14:11.189252228Z"}]'
```

### Enroll a watcher

#### Command usage

```php
php tests/scripts/watcher/enroll.php <SCENARIOS_JSON> <NAME> <OVERWRITE> <ENROLL_KEY> <TAGS_JSON>
```


#### Example

```bash
php tests/scripts/watcher/enroll.php  '["crowdsecurity/http-backdoors-attempts", "crowdsecurity/http-bad-user-agent"]' TESTWATCHER 0 YourEnrollKey '["tag1", "tag2"]'
```