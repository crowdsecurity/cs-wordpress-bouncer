# Full guide

This guide exposes you the main features of the plugin.

## Let's get started!

We will start using "live" mode. You'll understand what it is after try the stream mode.

* First, be sure to [get the stack installed using the docker-compose guide](install-with-docker-compose.md).

* open a terminal to display LAPI logs in realtime:

```bash
docker-compose logs -f crowdsec
```

* In wp-admin, [ensure the bouncer is configured with **live** mode](http://localhost:8050/wp-admin/admin.php?page=crowdsec_plugin) (stream mode disabled).

### Discover the cache system

* In a tab, visit the [public home](http://localhost:8050/). You're allowed because LAPI said your IP is clean.

> To avoid latencies when the clean IP browse the website, the bouncer will keep this information in cache for 30 seconds (you can change this value in the [avdanced settings page](http://localhost:8050/wp-admin/admin.php?page=crowdsec_advanced_settings)). In other words, LAPI will not be requested to check this IP for the next 30 seconds.

 * You can call the website as many times as you want, the cache system will take relay during the ban period and so LAPI will not be disturbed. The ban decision will stay in cache for the full ban duration. Then the [public home](http://localhost:8050/) should be available again.

 ### Try ban remediation

* If you want to skip this delay, feel free to [clear the cache in the wp-admin](http://localhost:8050/wp-admin/admin.php?page=crowdsec_plugin).

The `DOCKER_HOST_IP` environnement variable is initialized via a call to:

```bash
source ./load-env-vars.sh
```

* In a terminal, ban your own IP for 4 hours:

```bash

# Ban your own IP for 4 hours:
docker-compose exec crowdsec cscli decisions add --ip ${DOCKER_HOST_IP} --duration 4h --type ban
```

* Immediately, the [public home](http://localhost:8050/) is now locked with a short message to explain you that you are banned.

### Try "captcha" remediation


* Now, request captcha for your own IP for 15m:

```bash

# Clear all existing decisions
docker-compose exec crowdsec cscli decisions delete --all

# Add a captcha
docker-compose exec crowdsec cscli decisions add --ip ${DOCKER_HOST_IP} --duration 15m --type captcha
```

* The [public home](http://localhost:8050/) now request you to fill a captcha.

* Unless you manage to solve the captcha, you'll not be able to access the website.

> Note: when you resolve the captcha in your browser, the associated PHP session is considered as sure.
> If you remove the captcha decision with `cscli`, then you add a new captcha decision for your IP, you'll not be prompted for the current PHP session. To view the captcha page, You can force using a new PHP session opening the front page with incognito mode.

## Stream mode, for the high traffic websites

With live mode, as you tried it just before, each time a user arrives to the website for the first time, a call is made to LAPI. If the traffic on your website is high, the bouncer will call LAPI very often.

To avoid this, LAPI offers a "stream" mode. The decisions list is updated at a predefined frequency and kept in cache. Let's try it!

> This bouncer uses the WordPress cron system. For demo purposes, we encourage you to [install the WP-Control plugin](http://localhost:8050/wp-admin/plugin-install.php?s=wp-control&tab=search&type=term), a plugin to view and control each Wordpress Cron task jobs.

First, clear the previous decisions:

```bash
# Clear all existing decisions
docker-compose exec crowdsec cscli decisions delete --all
```

* Then enable "stream" mode [right here](http://localhost:8050/wp-admin/admin.php?page=crowdsec_advanced_settings) and set the resync frequency to 30 seconds. If you installed WP-Control plugin, you can see that a new cron tak has just been added here http://localhost:8050/wp-admin/tools.php?page=crontrol_admin_manage_page.

* As the whole blocklist has just been loaded in cache (0 decision!), your IP is allowed. The [public home](http://localhost:8050/) is available.

* Now, if you ban your IP for 4h:

```bash
docker-compose exec crowdsec cscli decisions add --ip ${DOCKER_HOST_IP} --duration 4h --type ban
```

* In less than 30 seconds your IP will be banned and the [public home](http://localhost:8050/) will be locked.

Conclusion: with the stream mode, LAPI decisions are fetched on a regular basis rather than being called when user arrives for the first time.

# Try Redis or Memcached

In order to get better performances, you can switch the cache technology.

The docker-compose file started 2 unused containers, redis and memcached.

Let's try **Redis**!

- Just go to the [advanced settings](http://localhost:8050/wp-admin/admin.php?page=crowdsec_advanced_settings) page
- select the **Caching technology** named "Redis" and
- type `redis://redis:6379` in the "Redis DSN" field.

Very similar with **Memcached**!

- Just go to the [advanced settings](http://localhost:8050/wp-admin/admin.php?page=crowdsec_advanced_settings) page
- select the **Caching technology** named "Memcached" and
- type `memcached://memcached:11211` in the "Memcached DSN" field.


# Statistics

The bouncer has a stats page indicating each time :
- an IP has been banned by your website, or
- when a captcha has been presented to an IP visiting your website
- when a captcha has been solved or not.