![CrowdSec Logo](images/logo_crowdsec.png)
# CrowdSec LAPI PHP client

## User Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Description](#description)
- [Features](#features)
- [Quick start](#quick-start)
  - [Installation](#installation)
  - [Bouncer client instantiation](#bouncer-client-instantiation)
    - [LAPI calls](#lapi-calls)
- [Bouncer client configurations](#bouncer-client-configurations)
  - [LAPI url](#lapi-url)
  - [AppSec url](#appsec-url)
  - [Authorization type for connection](#authorization-type-for-connection)
  - [Settings for Api key authorization](#settings-for-api-key-authorization)
    - [Api key](#api-key)
  - [Settings for TLS authorization](#settings-for-tls-authorization)
    - [Bouncer certificate path](#bouncer-certificate-path)
    - [Bouncer key path](#bouncer-key-path)
    - [Peer verification](#peer-verification)
    - [CA certificate path](#ca-certificate-path)
  - [Settings for LAPI timeout](#settings-for-lapi-timeout)
    - [LAPI timeout](#lapi-timeout)
    - [LAPI connect timeout](#lapi-connect-timeout)
  - [Settings for AppSec timeout](#settings-for-appsec-timeout)
    - [AppSec timeout](#appsec-timeout)
    - [AppSec connect timeout](#appsec-connect-timeout)
  - [User Agent suffix](#user-agent-suffix)
  - [User Agent version](#user-agent-version)
- [Override the curl request handler](#override-the-curl-request-handler)
  - [Custom implementation](#custom-implementation)
  - [Ready to use `file_get_contents` implementation](#ready-to-use-file_get_contents-implementation)
- [Example scripts](#example-scripts)
  - [Get decisions stream](#get-decisions-stream)
    - [Command usage](#command-usage)
    - [Example usage](#example-usage)
  - [Get filtered decisions](#get-filtered-decisions)
    - [Command usage](#command-usage-1)
    - [Example](#example)
  - [Get AppSec decision](#get-appsec-decision)
    - [Command usage](#command-usage-2)
    - [Example](#example-1)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Description

This client allows you to interact with the CrowdSec Local API (LAPI).

## Features

- CrowdSec LAPI Bouncer available endpoints
  - Retrieve decisions stream list
  - Retrieve decisions for some filter
  - Retrieve AppSec decision
- Overridable request handler (`curl` by default, `file_get_contents` also available)


## Quick start

### Installation

First, install CrowdSec LAPI PHP Client via the [composer](https://getcomposer.org/) package manager:

```bash
composer require crowdsec/lapi-client
```

Please see the [Installation Guide](./INSTALLATION_GUIDE.md) for mor details.

### Bouncer client instantiation

To instantiate a bouncer client, you have to:


- Pass its `configs` array as a first parameter. You will find below [the list of other available 
  settings](#bouncer-client-configurations).


- Optionally, you can pass an implementation of the `AbstractRequestHandler` (from the `crowdsec/common` dependency 
  package) as a second parameter. By default, a `Curl` request handler will be used.


- Optionally, to log some information, you can pass an implementation of the `Psr\Log\LoggerInterface` as a third 
  parameter. You will find an example of such implementation with the provided `Logger\FileLog` class of the 
  `crowdsec/common` dependency package.

```php
use CrowdSec\LapiClient\Bouncer;
use Crowdsec\LapiClient\Storage\FileStorage;

$configs = [
    'auth_type' => 'api_key',
    'api_url' => 'https://your-crowdsec-lapi-url:8080',
    'appsec_url' => 'https://your-crowdsec-app-sec-url:7422',
    'api_key' => '**************************',
];
$client = new Bouncer($configs);
````

#### LAPI calls

Once your bouncer client is instantiated, you can perform the following calls:


##### Get Decisions stream list

To retrieve the list of top decisions, you can do the following call:

```php
$client->getStreamDecisions($startup, $filter);
```

- The first parameter `$startup` is a boolean:
  - When the `$startup` flag is true, all the decisions are returned.
  - When the `$startup` flag is false, only the decisions updates (add or remove) from the last stream call are returned.

- The second parameter `$filter` is an array. Please see the [CrowdSec LAPI documentation](https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/bouncers/getDecisionsStream) for more details about available 
  filters (scopes, origins, scenarios, etc.).


##### Get filtered Decisions

To retrieve information about existing decisions, you can do the following call:

```php
$client->getFilteredDecisions($filter);
```

The `$filter` parameter is an array. Please see the [CrowdSec LAPI documentation](https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/bouncers/getDecisions) for more details about available filters (scope, value, type, etc.).


##### Get AppSec decision

To retrieve an AppSec decision, you can do the following call:

```php
$client->getAppSecDecision($headers, $rawBody);
```

The `$headers` parameter is an array containing the headers of the forwarded request and some required headers for the AppSec decision.

The `$rawBody` parameter is optional and must be used if the forwarded request contains a body. It must be a string.

Please see the [CrowdSec AppSec documentation](https://docs.crowdsec.net/docs/appsec/intro/) for more details.


## Bouncer client configurations

The first parameter `$configs` of the Bouncer constructor can be used to pass the following settings:


### LAPI url

```php
$configs = [
        ... 
        'api_url' => 'https://your-crowdsec-lapi-url:8080'
        ...
];
```

Define the URL to your LAPI server, default to `http://localhost:8080`.

### AppSec url

```php
$configs = [
        ... 
        'appsec_url' => 'http://your-crowdsec-app-sec-url:7422'
        ...
];
```

Define the AppSec URL to your LAPI server, default to `http://localhost:7422`.

### Authorization type for connection

```php
$configs = [
        ... 
        'auth_type' => 'api_key'
        ...
];
```

The `auth_type` setting only accepts two values : `api_key` and `tls`. 

This setting is not required. If you don't set any value, `api_key` will be used by default.

The `api_key` value means that you will also add an `api_key` setting (see below) and that this Api key will be used to connect LAPI.

The `tls` value means that you will use some SSL certificates to connect LAPI. Thus, you will have to take care about the 
followings settings too : `tls_cert_path`, `tls_key_path`, `tls_verify_peer`, `tls_ca_cert_path`.

TLS authentication is only available if you use CrowdSec agent with a version superior to 1.4.0


### Settings for Api key authorization


#### Api key

```php
$configs = [
        ... 
        'api_key' => '********************************'
        ...
];
```

Key generated by the cscli (CrowdSec cli) command like `cscli bouncers add my-bouncer-name`


Only required if you choose `api_key` as `auth_type`


### Settings for TLS authorization


#### Bouncer certificate path

```php
$configs = [
        ... 
        'tls_cert_path' => '/var/www/html/cfssl/bouncer.pem'
        ...
];
```

Absolute path to the bouncer certificate. 

Only required if you choose `tls` as `auth_type`.


#### Bouncer key path

```php
$configs = [
        ... 
        'tls_key_path' => '/var/www/html/cfssl/bouncer-key.pem'
        ...
];
```

Absolute path to the bouncer key.

Only required if you choose `tls` as `auth_type`.

#### Peer verification

```php
$configs = [
        ... 
        'tls_verify_peer' => true
        ...
];
```

This option determines whether request handler verifies the authenticity of the peer's certificate.

When negotiating a TLS or SSL connection, the server sends a certificate indicating its identity. 
If `tls_verify_peer` is set to true, request handler verifies whether the certificate is authentic. 
This trust is based on a chain of digital signatures, rooted in certification authority (CA) certificates you supply using the `tls_ca_cert_path` setting below.


#### CA certificate path

```php
$configs = [
        ... 
        'tls_ca_cert_path' => '/var/www/html/cfssl/ca-chain.pem'
        ...
];
```

Absolute path to the CA used to process peer verification.

Only required if you choose `tls` as `auth_type` and `tls_verify_peer` is `true`.

### Settings for LAPI timeout

#### LAPI timeout

```php
$configs = [
        ... 
        'api_timeout' => 15
        ...
];
```

This setting is not required.

This is the maximum number of seconds allowed to execute a LAPI request.

It must be an integer. If you don't set any value, default value is 120. If you set a negative value, timeout is unlimited.

#### LAPI connect timeout

```php
$configs = [
        ... 
        'api_connect_timeout' => 5
        ...
];
```

This setting is not required and will only be used for the `Curl` request handler.

This is the maximum number of seconds allowed to connect to a LAPI server.

It must be an integer. If you don't set any value, default value is 300. If you set a negative value, timeout is unlimited.


### Settings for AppSec timeout

#### AppSec timeout

```php
$configs = [
        ... 
        'appsec_timeout_ms' => 300
        ...
];
```

This setting is not required.

This is the maximum number of milliseconds allowed to execute an AppSec request.

It must be an integer. If you don't set any value, default value is 400. If you set a negative value, timeout is unlimited.


#### AppSec connect timeout

```php
$configs = [
        ... 
        'appsec_connect_timeout_ms' => 100
        ...
];
```

This setting is not required and will only be used for the `Curl` request handler.

This is the maximum number of milliseconds allowed to connect to an AppSec server.

It must be an integer. If you don't set any value, default value is 150. If you set a negative value, timeout is unlimited.


### User Agent suffix

```php
$configs = [
        ... 
        'user_agent_suffix' => 'MySuffix'
        ...
];
```
This setting is not required.

Sending a `User-Agent` header during a LAPI call is mandatory. By default, user agent will be `csphplapi/vX.Y.Z` where 
`vX.Y.Z` is the current release version of this library.

You can add a custom suffix to this value by using the `user_agent_suffix` setting. It must be a string matching the regular expression `#^[A-Za-z0-9]{0,16}$#`.

With the example setting above, result will be  `csphplapi_MySuffix/vX.Y.Z`.

### User Agent version

```php
$configs = [
        ... 
        'user_agent_version' => 'v2.3.0'
        ...
];
```
This setting is not required.

As mentioned above, default user agent is `csphplapi/vX.Y.Z` where `vX.Y.Z` is the current release version of this
library.

You can add a custom version to this value by using the `user_agent_version` setting. It must be a string matching the regular expression `#^v\d{1,4}(\.\d{1,4}){2}$#`.

With the example setting above, result will be  `csphplapi/v2.3.0`.


## Override the curl request handler

### Custom implementation

By default, the `Bouncer` object will do curl requests to call the LAPI. If for some reason, you don't want to 
use curl then you can create your own request handler class and pass it as a second parameter of the `Bouncer` 
constructor. 

Your custom request handler class must implement the `RequestHandlerInterface` interface of the `crowdsec/common` 
dependency, and you will have to explicitly write an `handle` method:

```php
<?php

use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\RequestHandler\RequestHandlerInterface;

class CustomRequestHandler implements RequestHandlerInterface
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

Once you have your custom request handler, you can instantiate the bouncer that will use it:

```php
use CrowdSec\LapiClient\Bouncer;
use CustomRequestHandler;

$requestHandler = new CustomRequestHandler();

$client = new Bouncer($configs, $requestHandler);
```

Then, you can make any of the LAPI calls that we have seen above.


### Ready to use `file_get_contents` implementation

This client comes with a `file_get_contents` request handler that you can use instead of the default curl request 
handler. To use it, you should instantiate it and pass the created object as a parameter:

```php
use CrowdSec\LapiClient\Bouncer;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;

$requestHandler = new FileGetContents($configs);

$client = new Bouncer($configs, $requestHandler);
```

## Example scripts


You will find some ready-to-use php scripts in the `tests/scripts` folder. These scripts could be useful to better 
understand what you can do with this client. 

As Bouncer methods need at least an array as parameter, we use a json format in command line.


### Get decisions stream

#### Command usage

```php
php tests/scripts/bouncer/decisions-stream.php <STARTUP> <FILTER_JSON> <BOUNCER_KEY> <LAPI_URL>
```

#### Example usage

```bash
php tests/scripts/bouncer/decisions-stream.php 1 '{"scopes":"Ip"}' 92d3de1dde6d354b771d63035cf5ef83 https://crowdsec:8080 
```

Or, with the `file_get_contents` handler:

```bash
php tests/scripts/bouncer/request-handler-override/decisions-stream.php 1 '{"scopes":"Ip"}' 92d3de1dde6d354b771d63035cf5ef83 https://crowdsec:8080
```

### Get filtered decisions

#### Command usage

```bash
php tests/scripts/bouncer/decisions-filter.php <FILTER_JSON> <BOUNCER_KEY> <LAPI_URL>
```

#### Example

```bash
php tests/scripts/bouncer/decisions-filter.php '{"scope":"ip"}' 92d3de1dde6d354b771d63035cf5ef83 https://crowdsec:8080 
```

Or, with the `file_get_contents` handler:

```bash
php tests/scripts/bouncer/request-handler-override/decisions-filter.php '{"scopes":"Ip"}' 92d3de1dde6d354b771d63035cf5ef83 https://crowdsec:8080
```

### Get AppSec decision

#### Command usage

```bash
php tests/scripts/bouncer/appsec-decision.php <BOUNCER_KEY> <HEADERS_JSON> <APPSEC_URL> 
[<RAW_BODY_STRING>]
```

#### Example

```bash
php tests/scripts/bouncer/appsec-decision.php 'KWurslwIaE2aZSZjYU9mQAWTFb6AHiPTFNTsYTZvoAU' '{"X-Crowdsec-Appsec-Ip":"1.2.3.4","X-Crowdsec-Appsec-Uri":"/login","X-Crowdsec-Appsec-Host":"example.com","X-Crowdsec-Appsec-Verb":"POST","X-Crowdsec-Appsec-User-Agent":"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0"}' http://crowdsec:7422 'class.module.classLoader.resources.'
```
