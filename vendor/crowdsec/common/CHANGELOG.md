# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## SemVer Public API


The [public API](https://semver.org/spec/v2.0.0.html#spec-item-1) of this library consists of all public or protected methods, properties and constants belonging to 
the `src` folder.

As far as possible, we try to adhere to [Symfony guidelines](https://symfony.com/doc/current/contributing/code/bc.html#working-on-symfony-code) when deciding whether a change is a breaking change or not.

---

## [2.3.1](https://github.com/crowdsecurity/php-common/releases/tag/v2.3.1) - 2024-10-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v2.3.0...v2.3.1)


### Fixed

- Remove `Host` header for `file_get_contents` request handler
- Check status code before trying to decode the response body

---

## [2.3.0](https://github.com/crowdsecurity/php-common/releases/tag/v2.3.0) - 2024-10-04
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v2.2.0...v2.3.0)


### Added

- Add AppSec requests support

### Changed

- Throws a `CrowdSec\Common\Client\TimeoutException` for `curl` and `file_get_contents` request handlers when a 
  timeout is detected

---

## [2.2.0](https://github.com/crowdsecurity/php-common/releases/tag/v2.2.0) - 2023-12-07
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v2.1.1...v2.2.0)


### Added

- Add `api_connect_timeout` configuration for `Curl` request handler


---


## [2.1.1](https://github.com/crowdsecurity/php-common/releases/tag/v2.1.1) - 2023-07-12
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v2.1.0...v2.1.1)


### Fixed

- Fix scenario regular expression to handle longer name


---

## [2.1.0](https://github.com/crowdsecurity/php-common/releases/tag/v2.1.0) - 2023-03-30
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v2.0.0...v2.1.0)


### Added

- Add `no_rotation` configuration for `FileLog` logger


---


## [2.0.0](https://github.com/crowdsecurity/php-common/releases/tag/v2.0.0) - 2023-03-09
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v1.3.0...v2.0.0)


### Changed 

- *Breaking change*: If not null, the second param of the `AbstractClient::__contruct` method must implement 
  `RequestHandlerInterface`
- Change visibility of `RequestHandler/FileGetContents::convertHeadersToString` method from private to protected

### Added

- Add `ORIGIN_CAPI` and `ORIGIN_LISTS` constants


---


## [1.3.0](https://github.com/crowdsecurity/php-common/releases/tag/v1.3.0) - 2023-02-23
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v1.2.0...v1.3.0)


### Added

- Add `VERSION_REGEX` constant

---

## [1.2.0](https://github.com/crowdsecurity/php-common/releases/tag/v1.2.0) - 2023-02-02
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v1.1.0...v1.2.0)


### Added

- Add default `Exception` class
- Add `ConsoleLog` logger
- Log message format can be modified with a `format` configuration

---

## [1.1.0](https://github.com/crowdsecurity/php-common/releases/tag/v1.1.0) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v1.0.0...v1.1.0)


### Added

- Add `RequestHandlerInterface` implemented by the `AbstractRequestHandler` class 

---

## [1.0.0](https://github.com/crowdsecurity/php-common/releases/tag/v1.0.0) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-common/compare/v0.0.1...v1.0.0)

### Changed

- Change version to `1.0.0`: first stable release

### Added

- Add public API declaration

---


## [0.0.1](https://github.com/crowdsecurity/php-common/releases/tag/v0.0.1) - 2023-01-26
### Added
- Initial release
