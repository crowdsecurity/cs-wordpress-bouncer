<?php

define('CROWDSEC_LOG_PATH', CROWDSEC_PLUGIN_PATH.'/logs/crowdsec.log');
define('CROWDSEC_CACHE_PATH', CROWDSEC_PLUGIN_PATH.'/.cache');

define('CROWDSEC_BOUNCING_LEVEL_DISABLED', 'bouncing_disabled');
define('CROWDSEC_BOUNCING_LEVEL_FLEX', 'flex_boucing');
define('CROWDSEC_BOUNCING_LEVEL_NORMAL', 'normal_boucing');
define('CROWDSEC_BOUNCING_LEVEL_PARANOID', 'paranoid_boucing');

define('CROWDSEC_CACHE_SYSTEM_PHPFS', 'phpfs');
define('CROWDSEC_CACHE_SYSTEM_REDIS', 'redis');
define('CROWDSEC_CACHE_SYSTEM_MEMCACHED', 'memcached');

define('CROWDSEC_CAPTCHA_TECHNOLOGY_LOCAL', 'local');
define('CROWDSEC_CAPTCHA_TECHNOLOGY_RECAPTCHA', 'recaptcha');

define('CROWDSEC_BOUNCER_USER_AGENT', 'WordPress CrowdSec Bouncer/v0.4.0');
