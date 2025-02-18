<?php

use CrowdSecWordPressBouncer\Bouncer;

require_once __DIR__ . '/options-config.php';

use CrowdSecBouncer\BouncerException;
use CrowdSecWordPressBouncer\Constants;
define('CROWDSEC_REFRESH_BLOCKLIST_CRON_HOOK', 'crowdsec_refresh_blocklist_cron_hook');
define('CROWDSEC_REFRESH_BLOCKLIST_CRON_INTERVAL', 'crowdsec_refresh_blocklist_cron_interval');
define('CROWDSEC_PUSH_USAGE_METRICS_CRON_HOOK', 'crowdsec_push_usage_metrics_cron_hook');
define('CROWDSEC_PUSH_USAGE_METRICS_CRON_INTERVAL', 'crowdsec_push_usage_metrics_cron_interval');

// Create a WP custom cron interval (overwrite previous if any).
add_filter('cron_schedules', function ($schedules) {
    $refreshFrequency = is_multisite() ?
        (int) get_site_option('crowdsec_stream_mode_refresh_frequency') :
        (int) get_option('crowdsec_stream_mode_refresh_frequency');

    if ($refreshFrequency > 0) {
        $schedules[CROWDSEC_REFRESH_BLOCKLIST_CRON_INTERVAL] = [
            'interval' => $refreshFrequency,
            'display' => esc_html__('Every '.$refreshFrequency.' second(s)'),
        ];
    }
    $schedules[CROWDSEC_PUSH_USAGE_METRICS_CRON_INTERVAL] = [
        'interval' => 900,
        'display' => esc_html__('Every 900 seconds'),
    ];

    return $schedules;
});

function crowdSecRefreshBlocklist()
{
    try {
        $configs = getDatabaseConfigs();
        $bouncer = new Bouncer($configs);
        $bouncer->refreshBlocklistCache();
    } catch (BouncerException $e) {
        if(isset($bouncer) && $bouncer->getLogger()) {
            $bouncer->getLogger()->error('', [
                'type' => 'WP_EXCEPTION_WHILE_REFRESHING_CACHE',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}

function crowdSecPushUsageMetrics()
{
    try {
        $configs = getDatabaseConfigs();
        $bouncer = new Bouncer($configs);
        if(!empty($configs['crowdsec_usage_metrics'])) {
            $bouncer->pushUsageMetrics(Constants::BOUNCER_NAME, Constants::VERSION);
        }
    } catch (BouncerException $e) {
        if(isset($bouncer) && $bouncer->getLogger()) {
            $bouncer->getLogger()->error('', [
                'type' => 'WP_EXCEPTION_WHILE_PUSHING_USAGE_METRICS',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}

// Create the hook that the schedule will call
add_action(CROWDSEC_REFRESH_BLOCKLIST_CRON_HOOK, 'crowdSecRefreshBlocklist');
add_action(CROWDSEC_PUSH_USAGE_METRICS_CRON_HOOK, 'crowdSecPushUsageMetrics');

function unscheduleBlocklistRefresh()
{
    $timestamp = wp_next_scheduled(CROWDSEC_REFRESH_BLOCKLIST_CRON_HOOK);
    wp_unschedule_event($timestamp, CROWDSEC_REFRESH_BLOCKLIST_CRON_HOOK);
}

function scheduleBlocklistRefresh()
{
    // Remove existing schedule if any.
    unscheduleBlocklistRefresh();

    // Schedule "blocklist cache refresh" each <refresh interval>, the first execution starting now.
    wp_schedule_event(time(), CROWDSEC_REFRESH_BLOCKLIST_CRON_INTERVAL, CROWDSEC_REFRESH_BLOCKLIST_CRON_HOOK);
}

function unscheduleUsageMetricsPush()
{
    $timestamp = wp_next_scheduled(CROWDSEC_PUSH_USAGE_METRICS_CRON_HOOK);
    wp_unschedule_event($timestamp, CROWDSEC_PUSH_USAGE_METRICS_CRON_HOOK);
}

function scheduleUsageMetricsPush()
{
    // Remove existing schedule if any.
    unscheduleUsageMetricsPush();

    // Schedule "usage metrics push" each <push interval>, the first execution starting now.
    wp_schedule_event(time(), CROWDSEC_PUSH_USAGE_METRICS_CRON_INTERVAL, CROWDSEC_PUSH_USAGE_METRICS_CRON_HOOK);


}
