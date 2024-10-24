<?php

declare(strict_types=1);

namespace CrowdSec\Common;

/**
 * Main CrowdSec constants.
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
    /**
     * @var int the default connection timeout (time of connection phase in seconds) when calling a CrowdSec API
     */
    public const API_CONNECT_TIMEOUT = 300;
    /**
     * @var int the default timeout (total time of transfer operation in seconds) when calling a CrowdSec API
     */
    public const API_TIMEOUT = 120;
    /**
     * @var int the default connection timeout (time of connection phase in milliseconds) when calling AppSec endpoints
     */
    public const APPSEC_CONNECT_TIMEOUT_MS = 150;
    /**
     * @var int the default timeout (total time of transfer operation in milliseconds) when calling AppSec endpoints
     */
    public const APPSEC_TIMEOUT_MS = 400;
    /**
     * @var string The API-KEY auth type
     */
    public const AUTH_KEY = 'api_key';
    /**
     * @var string The TLS auth type
     */
    public const AUTH_TLS = 'tls';
    /**
     * @var string The date format for CrowdSec data
     */
    public const DATE_FORMAT = 'Y-m-d\TH:i:s.u\Z';
    /**
     * @var int The CrowdSec TTL for decisions (in seconds)
     */
    public const DURATION = 86400;
    /**
     * @var string The AppSec API key header name
     */
    public const HEADER_APPSEC_API_KEY = 'X-Crowdsec-Appsec-Api-Key';
    /**
     * @var string The AppSec host header name
     */
    public const HEADER_APPSEC_HOST = 'X-Crowdsec-Appsec-Host';
    /**
     * @var string The AppSec IP header name
     */
    public const HEADER_APPSEC_IP = 'X-Crowdsec-Appsec-Ip';
    /**
     * @var string The AppSec URI header name
     */
    public const HEADER_APPSEC_URI = 'X-Crowdsec-Appsec-Uri';
    /**
     * @var string The AppSec User-Agent header name
     */
    public const HEADER_APPSEC_USER_AGENT = 'X-Crowdsec-Appsec-User-Agent';
    /**
     * @var string The AppSec verb header name
     */
    public const HEADER_APPSEC_VERB = 'X-Crowdsec-Appsec-Verb';
    /**
     * @var string The LAPI API key header name
     */
    public const HEADER_LAPI_API_KEY = 'X-Api-Key';
    /**
     * @var string The LAPI User-Agent header name
     */
    public const HEADER_LAPI_USER_AGENT = 'User-Agent';
    /**
     * @var string The ISO8601 date regex
     */
    public const ISO8601_REGEX = '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#';
    /**
     * @var string The CrowdSec origin for decisions
     */
    public const ORIGIN = 'crowdsec';
    /**
     * @var string The AppSec origin for decisions
     */
    public const ORIGIN_APPSEC = 'appsec';
    /**
     * @var string The CAPI origin for decisions
     */
    public const ORIGIN_CAPI = 'capi';
    /**
     * @var string The LISTS origin for decisions
     */
    public const ORIGIN_LISTS = 'lists';
    /**
     * @var string The ban remediation
     */
    public const REMEDIATION_BAN = 'ban';
    /**
     * @var string The bypass remediation
     */
    public const REMEDIATION_BYPASS = 'bypass';
    /**
     * @var string The captcha remediation
     */
    public const REMEDIATION_CAPTCHA = 'captcha';
    /**
     * @var string The scenario regex
     */
    public const SCENARIO_REGEX = '#^[A-Za-z0-9]{0,16}\/[A-Za-z0-9_-]{0,64}$#';
    /**
     * @var string The CrowdSec country scope for decisions
     */
    public const SCOPE_COUNTRY = 'country';
    /**
     * @var string The CrowdSec Ip scope for decisions
     */
    public const SCOPE_IP = 'ip';
    /**
     * @var string The CrowdSec range scope for decisions
     */
    public const SCOPE_RANGE = 'range';
    /**
     * @var string The current version of this library
     */
    public const VERSION = 'v2.3.2';
    /**
     * @var string The version regex
     */
    public const VERSION_REGEX = '#^v\d{1,4}(\.\d{1,4}){2}$#';
}
