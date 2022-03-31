![CrowdSec Logo](images/logo_crowdsec.png)

# CrowdSec WordPress Bouncer

## Technical notes

<!-- START doctoc generated TOC please keep comment here to allow auto update -->
<!-- DON'T EDIT THIS SECTION, INSTEAD RE-RUN doctoc TO UPDATE -->
**Table of Contents**

- [How to use system CRON instead of wp-cron?](#how-to-use-system-cron-instead-of-wp-cron)

<!-- END doctoc generated TOC please keep comment here to allow auto update -->


We explain here each important technical decision used to design this
plugin.


### How to use system CRON instead of wp-cron?

Add `define('DISABLE_WP_CRON', true);` in `wp-config.php` then enter this command line on the wordpress host command line:

```bash
(crontab -l && echo "* * * * * wget -q -O - htt://<host>:<port>/wp-cron.php?doing_wp_cron >/dev/null 2>&1") | crontab -
```

> Note: replace <host>:<port> with the local url of your website

More info [here](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/).