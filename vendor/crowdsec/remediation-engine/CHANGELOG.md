# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


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
