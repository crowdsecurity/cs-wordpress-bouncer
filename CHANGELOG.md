# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [2.6.7](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.7) - 2024-07-26
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.6...v2.6.7)

### Added

- Add compatibility with WordPress 6.6

---

## [2.6.6](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.6) - 2024-06-20
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.5...v2.6.6)

### Fixed

- Remove Twig dependency to avoid conflict with other plugins or themes (see [issue 153](https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/153))

---

## [2.6.5](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.5) - 2024-06-20
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.4...v2.6.5)


- No change in this version (wrong release process)

---

## [2.6.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.4) - 2024-06-13
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.3...v2.6.4)

### Fixed

- Fix Redis connection error when using user and password in DSN

---


## [2.6.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.3) - 2024-04-05
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.2...v2.6.3)

### Added

- Add compatibility with WordPress 6.5

---

## [2.6.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.2) - 2024-03-29
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.1...v2.6.2)

### Fixed

- Use `CrowdSecWordPressBouncer` namespace to avoid conflict with other plugins or themes

---


## [2.6.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.1) - 2024-03-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.6.0...v2.6.1)

### Fixed

- Fix incorrect log and cache paths in admin view


## [2.6.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.6.0) - 2024-03-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.5.2...v2.6.0)

### Changed

- Move logs and cache folders to `wp-content/uploads/crowdsec` to avoid deletion on plugin update and pass checksum validation
- Write `standalone-settings.php` file only if the new setting `Enable auto_prepend_file mode` is on.

### Added

- Add a `Enable auto_prepend_file mode` setting.


---


## [2.5.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.5.2) - 2023-11-23
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.5.1...v2.5.2)

### Added

- Add compatibility with WordPress 6.4


---

## [2.5.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.5.1) - 2023-09-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.5.0...v2.5.1)

### Added

- Add compatibility with WordPress 6.3


---


## [2.5.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.5.0) - 2023-06-01
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.4.1...v2.5.0)

### Added

- Add WordPress multisite compatibility 


---


## [2.4.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.4.1) - 2023-04-28
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.4.0...v2.4.1)

### Changed

- No change. Release to test update process hook.


---


## [2.4.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.4.0) - 2023-04-28
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.3.1...v2.4.0)

### Changed

- Use absolute path for TLS files
- Use absolute path for geolocation files

### Added
- Add an action after plugin upgrade to recreate standalone settings file


---


## [2.3.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.3.1) - 2023-04-06
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.3.0...v2.3.1)

### Fixed

- Use root `.htaccess` instead of multiple subfolders `.htaccess`


---


## [2.3.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.3.0) - 2023-04-06
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.2.0...v2.3.0)

### Security

- Add `.htaccess` files to deny direct access of plugin sensitive folders


---


## [2.2.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.2.0) - 2023-03-30
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.1.0...v2.2.0)

### Changed

- Do not use cache tags
- Do not rotate log files

### Added
- Add tests for WordPress 6.2

---


## [2.1.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.1.0) - 2023-03-23
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.0.4...v2.1.0)

### Added

- Add a `custom_user_agent` setting for debug ([#95](https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/95))


### Fixed

- Fix error on fresh install because Api key is required even if bouncing is disabled


---


## [2.0.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.0.4) - 2023-03-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.0.3...v2.0.4)

### Fixed

- If a database option is empty, we add the default value to avoid configuration PHP error ([#133](https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/133))

---


## [2.0.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.0.3) - 2023-02-16
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.0.2...v2.0.3)

### Fixed
- If `display_errors` setting is `true`, error is thrown only if bouncer has been successfully instantiated

---

## [2.0.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.0.2) - 2023-02-16
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.0.1...v2.0.2)

### Fixed
- Cast missing database options to string if necessary ([#127](https://github.com/crowdsecurity/cs-wordpress-bouncer/issues/127))


---

## [2.0.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.0.1) - 2023-02-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v2.0.0...v2.0.1)

### Fixed
- Fix missing `TwigTest.php` in release zip that broke captcha and ban walls 
- Fix bad memcached dsn check
- Fix clean and bad ip resync values when disabling stream mode


---

## [2.0.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v2.0.0) - 2023-02-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.11.0...v2.0.0)

### Changed
- All source code has been refactored using new CrowdSec PHP librairies:
  - Logs messages have been changed
  - User Agent sent to CrowdSec LAPI has been changed to `csphplapi_WordPress/vX.Y.Z`

### Removed

- Remove `Geolocation save result` setting. To disable Geolocation result saving, we can set 0 in the `Geolocation 
  cache lifetime` setting
---

## [1.11.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.11.0) - 2022-12-22
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.10.0...v1.11.0)

### Added
- Add LAPI request timeout setting (default to 120 seconds)
---

## [1.10.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.10.0) - 2022-12-01
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.9.0...v1.10.0)
### Changed
- Modify ban and captcha walls templating for W3C validity
- Do not use cache tags for `memcached` as it is discouraged
- Replace unauthorized chars by underscore `_` in cache keys
### Added
- Add tests for WordPress 6.1

---

## [1.9.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.9.0) - 2022-09-15
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.8.1...v1.9.0)
### Added
- Add TLS authentication feature
### Fixed
- Fix false negative connection test from admin when `trust_ip_forward_array` setting is not in database
---

## [1.8.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.8.1) - 2022-08-18
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.8.0...v1.8.1)
### Fixed
- Set missing default values in settings
---
## [1.8.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.8.0) - 2022-08-04
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.7.0...v1.8.0)
### Added
- Add `use_curl` configuration: should be used if `allow_url_fopen` is disabled and `curl` is available
- Add `disable_prod_log` configuration

### Changed
- Change log path to `wp-content/plugins/crowdsec/logs`
- By default, the `bouncing_level` setting is `bouncing_disabled` (instead of `normal_bouncing`)

---
## [1.7.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.7.0) - 2022-07-21
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.6.0...v1.7.0)
### Added
- Add geolocation feature

### Changed
- Do not throw exception if empty api url as it is the default after a fresh install and activation
- Changed default value for some boolean value as WordPress config are always string
---
## [1.6.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.6.0) - 2022-06-30
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.5.1...v1.6.0)
### Added
- Add "Test bouncing" action in settings view
---
## [1.5.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.5.1) - 2022-06-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.5.0...v1.5.1)
### Added
- Add tests for WordPress 6.0
---
## [1.5.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.5.0) - 2022-06-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.4.3...v1.5.0)
### Added
- Add configuration to set captcha flow cache lifetime
### Changed
- Use cache instead of session to store some captcha flow values
### Fixed
- Fix wrong deleted decisions count during cache refresh
---
## [1.4.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.4.3) - 2022-05-13
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.4.2...v1.4.3)
### Fixed
- Do not bounce if headers are already sent
---
## [1.4.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.4.2) - 2022-05-13
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.4.1...v1.4.2)
### Added
- Add WordPress debug log if bouncer logger is not ready
---
## [1.4.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.4.1) - 2022-04-10
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.4.0...v1.4.1)
### Fixed
- Close the session after bounce process

---
## [1.4.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.4.0) - 2022-04-07
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.3.2...v1.4.0)
### Changed
- Do not bounce PHP CLI
---
## [1.3.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.3.2) - 2022-03-10
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.3.1...v1.3.2)
### Fixed
- Fix debug log for marketplace deployed version

---
## [1.3.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.3.1) - 2022-03-10
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.3.0...v1.3.1)
### Fixed
- Fix `gregwar/captcha` for PHP 8.1 compatibility (by using version 0.15.0 of `crowdsec/bouncer` lib)
---
## [1.3.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.3.0) - 2022-02-03
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.5...v1.3.0)
### Changed
- Use static settings only in standalone mode
---
## [1.2.5](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.5) - 2022-01-27
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.4...v1.2.5)
### Added
- Add test for WordPress 5.9
---
## [1.2.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.4) - 2021-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.3...v1.2.4)
### Fixed
- Fix CHANGELOG link in readme.txt
---
## [1.2.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.3) - 2021-12-16
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.2...v1.2.3)
### Added
- Add CHANGELOG file
---
## [1.2.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.2) - 2021-12-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.1...v1.2.2)
### Changed
- Fix service-contracts version to avoid svn error due to PHP 8 code style

---
## [1.2.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.1) - 2021-12-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.2.0...v1.2.1)
### Changed
- Fix symfony polyfill-mbstring version to avoid wordpress svn pre-commit hook error
- Fix PHP version to 7.2 as we have to run `composer install` on a PHP 7.2 environment
---
## [1.2.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.2.0) - 2021-12-09
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.1.2...v1.2.0)
### Added
- Add end to end GitHub actions test

### Removed
- Remove useless configuration to enable standalone mode. This mode should be entirely determined by the presence of
  an auto_prepend_file PHP directive (php.ini, Apache, nginx, ...)

### Fixed
- Fix issue that cause warning message error on front in standalone mode
- Fix behavior : bounce should not be done twice in standalone mode
---
## [1.1.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.1.2) - 2021-12-02
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.1.1...v1.1.2)
### Fixed
- Use displayErrors variable to decide if we throw error or not
---

## [1.1.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.1.1) - 2021-12-02
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.1.0...v1.1.1)
### Fixed
- Fix release script
---

## [1.1.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.1.0) - 2021-12-02
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.7...v1.1.0)
### Changed
- Use `0.14.0` version of crowdsec php lib
- Handle typo fixing for retro compatibility (`flex_boucing`=>`flex_bouncing` and `normal_boucing`=>`normal_bouncing`)
- Split of debug in 2 configurations : debug and display_errors
---

## [1.0.7](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.7) - 2021-10-22
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.6...v1.0.7)
### Added
- Add compatibility test for WordPress 5.8
---

## [1.0.6](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.6) - 2021-08-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.5...v1.0.6)
### Changed
- Handle invalid input Ip format when the scope decision is set to "Ip"
---

## [1.0.5](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.5) - 2021-07-01
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.4...v1.0.5)
### Changed
- Close php session after bouncing
---

## [1.0.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.4) - 2021-06-25
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.3...v1.0.4)
### Changed
- Fix a bug at install/update process of the plugin.
---

## [1.0.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.3) - 2021-06-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.2...v1.0.3)
### Fixed
- This release is just a small fix to let the WordPress Marketplace consider the "1.0.3" as stable and propose this
  version to be downloaded. (yes, the previous fix was not enough)
---

## [1.0.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.2) - 2021-06-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.1...v1.0.2)
### Fixed
- This release is just a small fix to let the WordPress Marketplace consider the "1.0.2" as stable and propose this
  version to be downloaded.

---

## [1.0.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.1) - 2021-06-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v1.0.0...v1.0.1)
### Changed
- Update the package metadata to indicate to the Wordpress Marketplace that this plugin has been successuly tested
with the latest Wordpress 5.7 release (PHP 7.3, 7.4, 8.0)
- Update E2E tests dependencies

### Fixed
- Fix a problem when running dev environment on linux hosts : the "enable_ipv6" docker compose attribute was no more
accepted since in docker compose v3.

---

## [1.0.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v1.0.0) - 2021-06-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.6.0...v1.0.0)
### Added

- Add Standalone mode: an option allowing the PHP engine to no longer have to load the WordPress core during the
  bouncing stage. To be able to apply this mode, the webmaster has to set the auto_prepend_file PHP flag to the
  script we provide.
- Add debug mode: user can enable the debug mode directly from the CrowdSec advanced settings panel. A more verbose log
  will be written when this flag is enabled.
- Add WordPress 5.7 support
- Add PHP 8.0 support

### Changed
- Store Plugin in a flat file. This is a step to prepare the standalone mode.
- Prevent proxies from caching the wall pages. When the WP is covered by a reverse proxy (like a CDN, Varnish, Nginx
  reverse proxy etc), the wall page (ban or catpcha) is no more cached.


### Fixed
- Fix incompatibilities with other plugin (session_start). When another plugin uses PHP sessions, using the two
  plugins together trigger a PHP notice (session_start already sent). This has been fixed.

---

## [0.6.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.6.0) - 2021-01-23
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.5.4...v0.6.0)
### Added
- Add ipv6 support
---

## [0.5.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.5.4) - 2021-01-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.5.3...v0.5.4)
### Changed
- Update doc
---

## [0.5.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.5.3) - 2021-01-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.5.2...v0.5.3)
### Changed
- Update doc and assets
---

## [0.5.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.5.2) - 2021-01-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.5.1...v0.5.2)
### Changed
- Update doc and assets

---

## [0.5.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.5.1) - 2021-01-14
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.5.0...v0.5.1)
### Changed
- Update doc and assets
---

## [0.5.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.5.0) - 2021-01-13
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.5...v0.5.0)
### Changed
- Allow user to customize public pages
---

## [0.4.5](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.5) - 2021-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.4...v0.4.5)
### Changed
- Update deps
- Use `.env` file for docker-compose
- Update doc
---

## [0.4.4](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.4) - 2021-01-12
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.3...v0.4.4)
### Changed
- Improve dev environment

## [0.4.3](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.3) - 2021-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.2...v0.4.3)
### Changed
- Improve log system
---

## [0.4.2](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.2) - 2021-01-05
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.1...v0.4.2)
### Changed
- Improve security
---

## [0.4.1](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.1) - 2020-12-26
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.4.0...v0.4.1)
### Added
- Add more tests

---

## [0.4.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.4.0) - 2020-12-24
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.3.0...v0.4.0)
### Added
- Add cdn ip ranges
- Add WordPress support from 4.9 to 5.6
- Add functional tests for every WordPress version
- Add wp scan dev tool

---

## [0.3.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.3.0) - 2020-12-22
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.2.0...v0.3.0)
### Added
- Add redis and memcached connection checks
- Make a lint pass

---

## [0.2.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.2.0) - 2020-12-22
[_Compare with previous release_](https://github.com/crowdsecurity/cs-wordpress-bouncer/compare/v0.1.0...v0.2.0)
### Added

- Use the new bouncer constructor syntax
- Allow hiding cs mentions
- Remove todo mentions
- Hide paranoid mode as it is wip
- Add versioning process
---

## [0.1.0](https://github.com/crowdsecurity/cs-wordpress-bouncer/releases/tag/v0.1.0) - 2020-12-22

### Added

- Initial release





















