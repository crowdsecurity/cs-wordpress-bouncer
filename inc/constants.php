<?php

$crowdsecRandomLogFolder = get_option('crowdsec_random_log_folder') ?: '';
define('CROWDSEC_LOG_PATH', CROWDSEC_PLUGIN_PATH."/logs/$crowdsecRandomLogFolder/prod.log");
define('CROWDSEC_DEBUG_LOG_PATH', CROWDSEC_PLUGIN_PATH."/logs/$crowdsecRandomLogFolder/debug.log");
define('CROWDSEC_CACHE_PATH', CROWDSEC_PLUGIN_PATH.'/.cache');

define('CROWDSEC_BOUNCER_USER_AGENT', 'WordPress CrowdSec Bouncer/v0.6.0');
