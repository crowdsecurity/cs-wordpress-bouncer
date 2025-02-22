# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## SemVer public API

The [public API](https://semver.org/spec/v2.0.0.html#spec-item-1) of this library consists of all public or protected methods, properties and constants belonging to the `src` folder.

As far as possible, we try to adhere to [Symfony guidelines](https://symfony.com/doc/current/contributing/code/bc.html#working-on-symfony-code) when deciding whether a change is a breaking change or not.

---

## [3.6.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.6.0) - 2025-01-31
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.5.0...v3.6.0)

### Changed

- Allow Monolog 3 package (Use `crowdsec/common` `^3.0.0` dependency)

---

## [3.5.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.5.0) - 2025-01-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.4.0...v3.5.0)

### Changed

- Allow Symfony 7 packages

---

## [3.4.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.4.0) - 2025-01-09
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.3.2...v3.4.0)

### Added

- Add `pushUsageMetrics` method to `Bouncer` class


---

## [3.3.2](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.3.2) - 2024-10-21
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.3.1...v3.3.2)

### Fixed

- Truncate long raw body in logs

---

## [3.3.1](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.3.1) - 2024-10-11
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.3.0...v3.3.1)

### Fixed

- Remove sensitive data from logs

---

## [3.3.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.3.0) - 2024-10-04
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.2.0...v3.3.0)

### Added

- Add `getAppSecDecision` method to `Bouncer` class
- Add `appsec_url`, `appsec_timeout_ms` and `appsec_connect_timeout_ms` configurations

### Changed

- Throws a `CrowdSec\LapiClient\TimeoutException` when a timeout is detected during client calls


---

## [3.2.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.2.0) - 2023-12-07
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.1.0...v3.2.0)

### Added


- Add `api_connect_timeout` configuration


---

## [3.1.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.1.0) - 2023-04-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v3.0.0...v3.1.0)

### Changed


- `api_url` configuration must not be empty


---

## [3.0.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v3.0.0) - 2023-03-09
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v2.0.0...v3.0.0)

### Changed


- *Breaking change*: Use `crowdsec/common` `^2.0.0` dependency instead of `^1.2.0`
- *Breaking change*: If not null, the second param of `Bouncer` constructor must implement `RequestHandlerInterface`


---

## [2.0.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v2.0.0) - 2023-02-02
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v1.0.1...v2.0.0)

### Changed

- Use `crowdsec/common` package as a dependency for code factoring  

  - *Breaking change*: Use `CrowdSec\Common` classes for the following files and folder:
    - `HttpMessage`
    - `Logger`
    - `RequestHanlder`
    - `AbstractClient.php`

  - *Breaking change*: If not null, the second param of `Bouncer` constructor must be of type 
    `CrowdSec\Common\Client\RequestHandler\AbstractRequestHandler` 
  - *Breaking change*: Move `Bouncer` constants in `Constants`

---


## [1.0.1](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v1.0.1) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v1.0.0...v1.0.1)

### Added

- Add public API declaration

---


## [1.0.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v1.0.0) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v0.4.0...v1.0.0)

### Changed

- Change version to `1.0.0`: first stable release

---


## [0.4.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v0.4.0) - 2023-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v0.3.0...v0.4.0)

### Changed

- Unexpected configuration keys are automatically removed by a new `cleanConfigs` method
- Update some logs

---


## [0.3.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v0.3.0) - 2023-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v0.2.0...v0.3.0)

### Changed

- Do not throw error on LAPI 404 response
- Use compressed requests for `Curl`
- Use message log instead of a context message field

---


## [0.2.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v0.2.0) - 2022-12-29
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v0.1.0...v0.2.0)

### Changed

- Lowercase all scope constants (`ip`, `range`, `country`)

---

## [0.1.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v0.1.0) - 2022-12-23
[_Compare with previous release_](https://github.com/crowdsecurity/php-lapi-client/compare/v0.0.1...v0.1.0)

### Changed

- Increase default timeout to 120 seconds and allow unlimited timeout for negative `api_timeout` setting value

### Added
- Add `user_agent_version` configuration

---


## [0.0.1](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v0.0.1) - 2022-12-08
### Added
- Initial release
