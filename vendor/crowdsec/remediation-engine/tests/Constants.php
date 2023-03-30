<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests;

use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\Constants as RemConstants;

/**
 * Every constant for testing.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Constants
{
    public const IP_RANGE = '24';
    public const IP_V4 = '1.2.3.4';
    public const IP_V4_2 = '5.6.7.8';
    public const IP_V4_3 = '9.10.11.12';
    public const IP_V4_2_CACHE_KEY = RemConstants::SCOPE_IP . AbstractCache::SEP . self::IP_V4_2;
    /*
     * 66051 = intdiv(ip2long(IP_V4),256)
     */
    public const IP_V4_BUCKET_CACHE_KEY = AbstractCache::IPV4_BUCKET_KEY . AbstractCache::SEP .
                                          '66051';
    public const IP_V4_CACHE_KEY = RemConstants::SCOPE_IP . AbstractCache::SEP . self::IP_V4;
    public const IP_V4_3_CACHE_KEY = RemConstants::SCOPE_IP . AbstractCache::SEP . self::IP_V4_3;
    public const IP_V4_RANGE_CACHE_KEY = RemConstants::SCOPE_RANGE . AbstractCache::SEP . self::IP_V4 .
                                         AbstractCache::SEP .
                                         self::IP_RANGE;
    public const IP_V6 = '2001:0db8:85a3:0000:0000:8a2e:0370:7334';
    public const IP_V6_CACHE_KEY = '2001_0db8_85a3_0000_0000_8a2e_0370_7334';
    public const TMP_DIR = '/tmp';
    public const IP_JAPAN = '210.249.74.42';
    public const IP_FRANCE = '78.119.253.85';
    public const CACHE_DURATION = 100;

    /**
     * @var string The user agent suffix used to send request to CAPI
     */
    public const USER_AGENT_SUFFIX = 'PHPCAPITEST';

    /**
     * @var string The machine id prefix used to send request to CAPI
     */
    public const MACHINE_ID_PREFIX = 'capiclienttest';
}
