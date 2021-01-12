=== CrowdSec ===
Contributors: crowdsec
Donate link: https://crowdsec.net/
Tags: security, firewall, malware scanner, two factor authentication, captcha, waf, web app firewall, mfa, 2fa
Requires at least: 4.9
Tested up to: 5.6
Stable tag: 0.5.0
Requires PHP: 7.2
License: MIT
License URI: https://opensource.org/licenses/MIT

CrowdSec is an open-source cyber security tool. This plugin blocks the detected attackers or display them a captcha to check their are not bots.

== Description ==

CrowdSec is composed of a behavior detection engine, able to block classical attacks like credential bruteforce, port scans, web scans, etc.

Based on attack blocked, and after curation of those signals to avoid false positives and poisoning, a global IP reputation DB is maintained and shared with all network members.

This wordpress plugin is a "bouncer", it blocks the detected attacks with two remediation systems: Ban or challenge detected attacker with a Captcha.


== Frequently Asked Questions ==

= What do I need to make CrowdSec works?  =

- You have to get a CrowdSec instance available from this server.
- On the server CrowdSec run, you have to generate a bouncer key.

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
* The user can customize the ban wall and captcha wall.

= 0.4 =
* User can set CDN IP ranges to trust.

= 0.3 =
* Add Redis and Memcached support for caching data.

= 0.2 =
* Make the public CrowdSec mentions hiddable.

= 0.1 =
* Avoid useless bouncing cases.
* Add advanced settings page.

== Upgrade Notice ==

= 0.5 =
The user can customize the ban wall and captcha wall.

== CrowdSec ==

You can:

1. Block aggresive IPs
2. Display a captcha for less aggresive IPs

Get more info on the [CrowdSec official website](https://crowdsec.net).