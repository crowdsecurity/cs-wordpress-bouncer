![CrowdSec Logo](images/logo_crowdsec.png)

# CrowdSec CAPI PHP client

## Installation Guide


<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [Requirements](#requirements)
- [Installation](#installation)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


## Requirements

- PHP >= 7.2.5
- required php extensions : `ext-json`

By default, each call to the CrowdSec CAPI use [cURL](https://www.php.net/manual/en/book.curl.php) to process 
http requests. Thus, if you are using this default curl request handler, then `ext-curl` is also required.

As an alternative, you can implement your own request handler or use the provided `file_get_contents` request handler. 

## Installation

Use `Composer` by simply adding `crowdsec/capi-client` as a dependency:

    composer require crowdsec/capi-client
