# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## SemVer public API


The [public API](https://semver.org/spec/v2.0.0.html#spec-item-1)  of this library consists of all public or protected methods, properties and constants belonging to the `src` folder.

---

## [3.3.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v3.3.0) - 2023-12-14
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v3.2.0...v3.3.0)

### Changed

- Update `crowdsec/common` dependency to `v2.2.0` (`api_connect_timeout` setting)
- Update `crowdsec/capi-client` dependency to `v3.1.0` (`api_connect_timeout` setting)
- Update `crowdsec/lapi-client` dependency to `v3.2.0` (`api_connect_timeout` setting)


---


## [3.2.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v3.2.0) - 2023-04-20
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v3.1.1...v3.2.0)

### Changed

- Store origin of decision in cache

### Added

- Store origin count of `getIpRemediation` in cache and provide a `getOriginsCount` helper method

### Deprecated

- Deprecate `AbstractRemediation::getRemediationFromDecisions`
- Deprecate `AbstractRemediation::sortDecisionsByRemediationPriority`


---

## [3.1.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v3.1.1) - 2023-03-24
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v3.1.0...v3.1.1)

### Fixed

- Do not set logger in Memcached cache to avoid silent error


---


## [3.1.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v3.1.0) - 2023-03-24
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v3.0.0...v3.1.0)

### Changed

- Instantiate provided Redis and PhpFiles caches without cache tags by default
- Do not cache CAPI decision with `0h` duration
- Set logger in cache adapter to log Symfony cache messages

### Added

- Add a boolean `use_cache_tags` setting for Redis and PhpFiles caches. Default to `false`.


---

## [3.0.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v3.0.0) - 2023-03-09
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v2.1.0...v3.0.0)

### Changed

- *Breaking change*: Update `crowdsec/capi-client` dependency to `v3.0.0` (CAPI V3 endpoints)
- *Breaking change*: Update `crowdsec/lapi-client` dependency to `v3.0.0`
- *Breaking change*: Update `crowdsec/common` dependency to `v2.0.0`
- *Breaking change*: Use custom error handler for `Memcached::getItem` method
- *Breaking change*: Rename `AbstractCache::updateItem` method to `upsertItem`
- *Breaking change*: The `cacheTag` string parameter of cache methods become a `tags` array
- Change visibility of `AbstractRemediation::parseDurationToSeconds` method from private to protected

### Added

- Handle blocklist decisions when pulling CAPI decisions


---

## [2.1.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v2.1.0) - 2023-02-10
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v2.0.0...v2.1.0)

### Added

- Add public method `getClient` for `LapiRemediation` and `CapiRemediation` classes


---

## [2.0.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v2.0.0) - 2023-02-02
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v1.0.1...v2.0.0)

### Changed

- *Breaking change*: Update `crowdsec/capi-client` to a new major version [2.0.0](https://github.com/crowdsecurity/php-capi-client/releases/tag/v2.0.0)
- *Breaking change*: Update `crowdsec/lapi-client` to a new major version [2.0.0](https://github.com/crowdsecurity/php-lapi-client/releases/tag/v2.0.0)
- Use `crowdsec/common` [package](https://github.com/crowdsecurity/php-common) as a dependency for code factoring

### Removed

- *Breaking change*: Remove `CrowdSec\RemediationEngine\Logger\FileLog` (replaced by `CrowdSec\Common\Logger\FileLog`)


---


## [1.0.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v1.0.1) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v1.0.0...v1.0.1)

### Added

- Add public API declaration

---

## [1.0.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v1.0.0) - 2023-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.7.0...v1.0.0)

### Changed

- Change version to `1.0.0`: first stable release

---

## [0.7.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.7.0) - 2023-01-13
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.6.1...v0.7.0)

### Changed

- Update `crowdsec/capi-client` dependency to `v0.11.0`


--- 


## [0.6.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.6.1) - 2023-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.6.0...v0.6.1)

### Fixed

- Fix some PHPDoc `@throws` tags


--- 


## [0.6.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.6.0) - 2023-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.5.0...v0.6.0)

### Changed

- For LAPI in live mode, we use and save retrieved IPv6 range scoped decisions as IP scoped decisions 
- Unexpected configuration keys are automatically removed by a new `cleanConfigs` method
- Do not try to retrieve range scoped decisions in cache for IPv6 IP as it is not yet implemented
- Update some logs
- Update `crowdsec/capi-client` dependency to `v0.10.0`
- Update `crowdsec/lapi-client` dependency to `v0.4.0`
- Do not throw exception for unknown prefix in `getCacheKey` method

### Added

- Add public method `getCacheStorage` for remediations
- Add public method `unsetIpVariables` for cache
- Add public method `clearGeolocationCache` for geolocation

--- 



## [0.5.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.5.0) - 2023-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.4.0...v0.5.0)

### Changed

- Use message log instead of a context message field
- Update `crowdsec/capi-client` dependency to `v0.9.0`
- Update `crowdsec/lapi-client` dependency to `v0.3.0`

### Added 

- Add cache warmup feature for LAPI 

--- 

## [0.4.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.4.0) - 2022-12-30
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.3.1...v0.4.0)

### Changed

- Modify some log format and severity level

### Added

- Add `symfony/cache` conflicts for Redis not working versions
- Add some relevant logs

--- 

## [0.3.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.3.1) - 2022-12-29
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.3.0...v0.3.1)

### Changed

- Update `symfony/cache` dependency to only exclude Redis not working versions

--- 


## [0.3.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.3.0) - 2022-12-29
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.2.0...v0.3.0)

### Changed
- Update `crowdsec/capi-client` dependency to `v0.7.0`
- Update `crowdsec/lapi-client` dependency to `v0.2.0`
- Fix `symfony/cache` dependency to `5.4.15` or `6.0.15` as `5.4.17` and `6.0.17` are buggy for Redis

--- 



## [0.2.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.2.0) - 2022-12-23
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.1.1...v0.2.0)

### Added
- Add geolocation feature to get remediation from `Country` scoped decisions (using MaxMind databases)

--- 


## [0.1.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.1.1) - 2022-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.1.0...v0.1.1)

### Changed
- Update `crowdsec/capi-client` dependency to `v0.6.0`
- Add PHP `8.2` in supported versions

--- 

## [0.1.0](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.1.0) - 2022-12-09
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.0.2...v0.1.0)

### Changed
- *Breaking change*: Make methods `AbstractRemediation::storeDecisions` and `AbstractRemediation::removeDecisions` protected instead of public and modify return type (`int` to `array`)

### Added
- Add LAPI remediation feature

--- 


## [0.0.2](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.0.2) - 2022-12-08
[_Compare with previous release_](https://github.com/crowdsecurity/php-remediation-engine/compare/v0.0.1...v0.0.2)
### Changed
- Update `crowdsec/capi-client` dependency to allow older `symfony/config` (v4) version

--- 

## [0.0.1](https://github.com/crowdsecurity/php-remediation-engine/releases/tag/v0.0.1) - 2022-12-02
### Added
- Initial release
