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
     * @var string The ISO8601 date regex
     */
    public const ISO8601_REGEX = '#^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})(.\d{6})?Z$#';
    /**
     * @var string The CrowdSec origin for decisions
     */
    public const ORIGIN = 'crowdsec';
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
    public const VERSION = 'v2.2.0';
    /**
     * @var string The version regex
     */
    public const VERSION_REGEX = '#^v\d{1,4}(\.\d{1,4}){2}$#';
}
