# FAQ

## How to use system CRON instead of wp-cron?

Add `define('DISABLE_WP_CRON', true);` in `wp-config.php` then enter this command line on the wordpress host command line:

```bash
(crontab -l && echo "* * * * * wget -q -O - htt://<host>:<port>/wp-cron.php?doing_wp_cron >/dev/null 2>&1") | crontab -
```

> Note: replace <host>:<port> with the local url of your website

More info [here](https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/).
