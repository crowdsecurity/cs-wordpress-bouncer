=== CrowdSec ===
Contributors: crowdsec
Donate link: https://crowdsec.net/
Tags: crowdsec-bouncer, wordpress, security, firewall, captcha, ip-scanner, ip-blocker, ip-blocking, ip-address, ip-database, ip-range-check, crowdsec, ban-hosts, ban-management, anti-hacking, hacker-protection, captcha-image, captcha-generator, captcha-generation, captcha-service
Requires at least: 4.9
Tested up to: 5.7
Stable tag: 1.0.5
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

This plugin blocks detected attackers or displays them a captcha to check they are not bots.

== Description ==

Note: You must first have CrowdSec [installed on your server. The installation is very simple](https://doc.crowdsec.net/Crowdsec/v1/getting_started/installation/#installation).

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
6. Advanced settings - Set the CDN or Reverse Proxies to trust.
7. The standard Captcha page
8. The standard Ban page
9. A Captcha wall page customization (text and colors)
10. A Ban wall page customization (text and colors)

== Changelog ==

= 0.5 =
* Users can customize both ban and captcha walls

= 0.4 =
* Users can set CDN IP ranges to trust

= 0.3 =
* Add Redis and Memcached support for caching data

= 0.2 =
* Make public CrowdSec mentions hiddable

= 0.1 =
* Avoid useless bouncing cases
* Add advanced settings page

== Upgrade Notice ==

= 0.5 =
The user can customize the ban wall and captcha wall

== CrowdSec ==

You can:

1. Block aggresive IPs
2. Display a captcha for less aggresive IPs

Get more info on the [CrowdSec official website](https://crowdsec.net).