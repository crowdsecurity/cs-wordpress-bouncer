# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Public API

The purpose of this section is to declare the public API of this library as required by  [item 1 of semantic versioning specification](https://semver.org/spec/v2.0.0.html#spec-item-1).

The public API of this library consists of all public or protected methods, properties and constants belonging to the `src` folder.

---


## [2.0.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v2.0.0) - 2023-02-02
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v1.0.1...v2.0.0)

### Changed

- Use `crowdsec/common` package as a dependency for code factoring

  - *Breaking change*: Use `CrowdSec\Common` classes for the following files and folder:
    - `HttpMessage`
    - `Logger`
    - `RequestHanlder`
    - `AbstractClient.php`

  - *Breaking change*: If not null, the third param of `Watcher` constructor must be of type
    `CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler`
  - *Breaking change*: Move `Watcher`, `Signal` and `Configuration\Signal` constants in `Constants`
- *Breaking change*: Remove deprecated `Watcher::createSignal` method


---

## [1.0.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v1.0.1) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v1.0.0...v1.0.1)

### Added

- Add public API declaration

---


## [1.0.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v1.0.0) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.11.0...v1.0.0)

### Changed

- Change version to `1.0.0`: first stable release

---

## [0.11.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.11.0) - 2023-01-13
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.10.0...v0.11.0)


### Added

- Add two signal builder helper methods: `buildSimpleSignalForIp` and `buildSignal`


### Deprecated

- Deprecate the `createSignal` method

---



## [0.10.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.10.0) - 2023-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.9.0...v0.10.0)


### Changed

- Unexpected configuration keys are automatically removed by a new `cleanConfigs` method
- Update some logs

---


## [0.9.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.9.0) - 2023-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.8.0...v0.9.0)


### Changed

- Do not throw error on CAPI 404 response
- Use compressed requests for `Curl` 
- Use message log instead of a context message field
- Do not log error on `formatResponseBody` to avoid double reporting [(#21)](https://github.com/crowdsecurity/php-capi-client/issues/21)
- Log retries as `info` and not as `error` [(#21)](https://github.com/crowdsecurity/php-capi-client/issues/21)

---


## [0.8.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.8.0) - 2022-12-30
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.7.0...v0.8.0)

### Added

- Add some relevant debug and error logs

### Changed

- `createSignal` throws now a `ClientException` instead of a generic `Exception` during date manipulation 

---

## [0.7.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.7.0) - 2022-12-29
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.6.2...v0.7.0)

### Changed

- Update validation rules for `user_agent_version` and `scenarios` settings

---

## [0.6.2](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.6.2) - 2022-12-26
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.6.1...v0.6.2)

### Fixed

- Fix `createSignal` by adding required decision id


---

## [0.6.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.6.1) - 2022-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.6.0...v0.6.1)

### Fixed

- Fix `Curl` unlimited timeout when negative value is configured in `api_timeout`


---

## [0.6.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.6.0) - 2022-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.5.0...v0.6.0)

### Changed

- Default `api_timeout` is now 120 seconds instead of 10 seconds

### Added
- Add `createSignal` helper method to create ready-to-use signal

---


## [0.5.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.5.0) - 2022-12-15
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.4.1...v0.5.0)

### Added
- Add `user_agent_version` configuration

---

## [0.4.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.4.1) - 2022-12-08
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.4.0...v0.4.1)

### Changed
- Allow older version (v4) of `symfony/config` dependency

---

## [0.4.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.4.0) - 2022-12-01
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.3.0...v0.4.0)

### Changed
- *Breaking change*: Make method `AbstractClient::sendRequest` private instead of public
- *Breaking change*: Make method `AbstractClient::request` protected instead of public

### Added
- Add `api_timeout` configuration
- Add an optional param `$configs` in `Curl` and `FileGetContents` constructors

---

## [0.3.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.3.0) - 2022-11-04
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.2.0...v0.3.0)

### Added
- Add optional logger parameter in client constructor 

---

## [0.2.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.2.0) - 2022-10-28
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.1.0...v0.2.0)

### Changed
- *Breaking change*: Missing `scenarios` key in `configs` will throw an exception

---

## [0.1.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.1.0) - 2022-10-21
[_Compare with previous release_](https://github.com/crowdsecurity/php-capi-client/compare/v0.0.1...v0.1.0)

### Changed
- *Breaking change*: Supported PHP versions starts with `7.2.5` (instead of `5.3`)
- *Breaking change*: `login` and `register` are now private methods
- *Breaking change*: `Watcher` constructor is totally changed : 
  - No more `password` and `machine_id` to pass, there are now automatically handled in background
  - An array of `configs` must be passed as first argument
  - An implementation of a `StorageInterface` must be passed as a second argument
- Change User Agent format: `csphpcapi_custom-suffix/vX.Y.Z`

### Added
- Add `enroll` public method

---

## [0.0.1](https://github.com/crowdsecurity/php-capi-client/releases/tag/v0.0.1) - 2022-06-24
### Added
- Initial release
