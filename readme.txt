=== CrowdSec ===
Contributors: crowdsec
Donate link: https://crowdsec.net/
Tags: security, captcha, ip-blocker, crowdsec, hacker-protection
Requires at least: 4.9
Tested up to: 6.6
Stable tag: 2.6.7
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

This plugin blocks detected attackers or displays them a captcha to check they are not bots.

== Description ==

Note: You must first have CrowdSec [installed on your server. The installation is very simple](https://docs.crowdsec.net/docs/next/bouncers/wordpress/).

CrowdSec is composed of a behavior detection engine, able to block classical attacks like credential bruteforce, port scans, web scans, etc.

Based on the type and number of blocked attacks, and after curation of those signals to avoid false positives and poisoning, a global IP reputation DB is maintained and shared with all network members.

This WordPress plugin is a "bouncer", which purpose is to block detected attacks with two remediation systems: ban or challenge detected attackers with a Captcha.


== Frequently Asked Questions ==

= What do I need to make CrowdSec work? =

- You have to install a CrowdSec instance on this server.
- You have to generate a bouncer key on the server on which CrowdSec is running.

== Screenshots ==

1. The general configuration page
2. Customize the wall pages - Adapt the "captcha wall" page text content with your own
3. Customize the wall pages - Adapt the "ban wall" page text content with your own
4. Customize the wall pages - Adapt the pages with your colors. You can also add custom CSS rules.
5. Advanced settings - Select the live or the stream mode. Select a cache engine (Classical file system, Redis or Memcached). Adjust the cache durations.
6. Advanced settings - Set the CDN or Reverse Proxies to trust and configure Geolocation feature.
7. The standard Captcha page
8. The standard Ban page
9. A Captcha wall page customization (text and colors)
10. A Ban wall page customization (text and colors)

== Changelog ==

= 2.6 (2024-03-14) =

- Move logs and cache folders to `wp-content/uploads/crowdsec` folder
- Add a `Enable auto_prepend_file mode` setting.

= 2.5 (2023-06-01) =

- Add WordPress multisite compatibility

= 2.4 (2023-04-28) =

- Use absolute path for TLS files
- Use absolute path for geolocation files
- Add an action after plugin upgrade to recreate standalone settings file

= 2.3 (2023-04-06) =

- Add access restriction for some folders

= 2.2 (2023-03-30) =

- Do not use cache tags
- Do not rotate log files

= 2.1 (2023-03-23) =

- Add custom User-Agent debug setting

= 2.0 (2023-02-09) =

- All source code has been refactored using new CrowdSec PHP librairies

= 1.11 (2022-12-22) =

- Add LAPI request timeout setting

= 1.10 (2022-12-01) =

- Modify ban and captcha walls templating for W3C validity

= 1.9 (2022-09-15) =

- Add TLS authentication option

= 1.8 (2022-08-04) =

- Add `use_curl` configuration: should be used if `allow_url_fopen` is disabled and `curl` is available
- Add `disable_prod_log` configuration
- Change log path to `wp-content/plugins/crowdsec/logs`
- By default, the `bouncing_level` setting is now `bouncing_disabled` (instead of `normal_bouncing`)

= 1.7 (2022-07-20) =

- Add geolocation feature

= 1.6 (2022-06-30) =

- Add "Test bouncing" action in settings view

= 1.5 (2022-06-09) =

- Use cache instead of session to store some values

= 1.4 (2022-04-07) =

- Do not bounce PHP CLI

= 1.3 (2022-02-03) =

- Use static settings only in standalone mode

= 1.2 (2021-12-09) =

- Fix issue that cause warning message error on front in standalone mode
- Fix behavior : bounce should not be done twice in standalone mode
- Remove useless configuration to enable standalone mode

= 1.1 (2021-12-02) =

- Use `0.14.0` version of crowdsec php lib
- Handle typo fixing for retro compatibility (`flex_boucing`=>`flex_bouncing` and `normal_boucing`=>`normal_bouncing`)
- Split of debug in 2 configurations : debug and display_errors

= 1.0 (2021-06-24) =

- Add Standalone mode: an option allowing the PHP engine to no longer have to load the WordPress core during the
  bouncing stage. To be able to apply this mode, the webmaster has to set the auto_prepend_file PHP flag to the
  script we provide.
- Add debug mode: user can enable the debug mode directly from the CrowdSec advanced settings panel. A more verbose log
  will be written when this flag is enabled.
- Add WordPress 5.7 support
- Add PHP 8.0 support


[Read the full Changelog](https://github.com/crowdsecurity/cs-wordpress-bouncer/blob/main/CHANGELOG.md)

== Upgrade Notice ==

= 2.5 =

If you are using the Multisite WordPress feature, CrowdSec Bouncer plugin has to be Network activated and CrowdSec settings have to be set in the Network admin.

= 2.4 =

After upgrading to 2.4, you have to define an absolute path for TLS files and geolocation databases (only if you use these features)

= 1.3 =
With this release, the `standalone-settings.php` file is used only in "standalone" mode. In the standard mode, configurations will be retrieved directly from database.

= 1.2 =
If you are using the standalone mode, you should upgrade as this release fixes some issues.

= 1.1 =
With this release, you can enable debug log without throwing error on browser as there are now two separate configurations.


== CrowdSec ==

You can:

1. Block aggressive IPs
2. Display a captcha for less aggressive IPs

Get more info on the [CrowdSec official website](https://crowdsec.net).
