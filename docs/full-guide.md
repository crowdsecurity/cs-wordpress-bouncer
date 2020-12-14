# Full guide

This scenario exposes you the main features this plugin is capable of.

## Let's get starting!

We will start using "live" mode. You'll understand what it is after try the stream mode.

* Install the stack before (cf contribute.md).

* open a terminal to display LAPI logs in realtime:

```bash
docker-compose logs -f crowdsec
```

* In wp-admin, [ensure the bouncer is configured with **live** mode](http://localhost/wp-admin/admin.php?page=crowdsec_plugin) (stream mode disabled).

### Discove the cache system

* In a tab, visit the [public home](http://localhost/). The way is free because LAPI said your IP is clean.

> To avoid latencies when the clean IP browse the website, the bouncer will keep this information in cache for 30 seconds (you can change this value in the [avdanced settings page](http://localhost/wp-admin/admin.php?page=crowdsec_advanced_settings)). In other words, LAPI will not be requested to check this IP for the next 30 seconds.

 * You can call the website many time as you want, the cache system will take relay during the ban period and so LAPI will not be disturbed. The ban decision will stay in cache for the full ban duration. Then the [public home](http://localhost/) should be available again.

 ### Try ban remediation

* As we don't want this delay, to take in account immediately the last unban, feel free to [clear the cache in the wp-admin](http://localhost/wp-admin/admin.php?page=crowdsec_plugin).

* In a term, ban your own IP for 4 hours:

```bash
# Get the Docker host IP from inside the crowdsec container
export CS_WP_HOST=`docker-compose exec crowdsec /sbin/ip route|awk '/default/ { printf $3 }'`

# Ban your own IP for 4 hours:
docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 4h --type ban
```

* Immediately, the [public home](http://localhost/) is now locked with a short message to explain you that you were ban.

### Try "captcha" remediation

* Now, request captcha for your own IP for 15m:

```bash
# Clear all existing decisions
docker-compose exec crowdsec cscli decisions delete --all

# Add a captcha
docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 15m --type captcha
```

* The [public home](http://localhost/) now request you to fill a captcha.

* While you fail resolving the captcha, you'll not be able to access the website.

## Stream mode, for the high traffic websites

With live mode, as you tried it just before, each time a user arrives to the website for the first time, a called is made to LAPI. If the traffic on your website is high, the bouncer will call LAPI very often.

To avoid this, LAPI offers a "stream" mode. The whole decisions list is updated for a predefined frequency. Let's try it!

> This bouncer used the WordPress cron system. For this demo purpose, we encourage you to [install the WP-Control plugin](http://localhost/wp-admin/plugin-install.php?s=wp-control&tab=search&type=term), a plugin to view and control each Wordpress Cron task jobs.

First, clear the previous decisions:

```bash
# Clear all existing decisions
docker-compose exec crowdsec cscli decisions delete --all
```

* Then enable "stream" mode [right here](http://localhost/wp-admin/admin.php?page=crowdsec_advanced_settings) and set the resync frequency to 30 seconds. If you installed WP-Control plugin, you can see that a new cron tak has just been added here http://localhost/wp-admin/tools.php?page=crontrol_admin_manage_page.

* As the whole blocklist has just been loaded in cache (0 decision!), your IP is now free. The [public home](http://localhost/) is now available.

* Now, if you ban your IP for 4h:

```bash
docker-compose exec crowdsec cscli decisions add --ip ${CS_WP_HOST} --duration 4h --type ban
```

* In less than 30 seconds your IP will be banned and the [public home](http://localhost/) will be locked.

Conclusion: with the stream mode, LAPI is never call directly when the website user arrives for the first time.

# Statistics

The bouncer has a stats page indicating each time :
- an IP has been banned by your website, or
- when a captcha has been presented to an IP visiting your website
- when a captcha the captcha has been solved or not.