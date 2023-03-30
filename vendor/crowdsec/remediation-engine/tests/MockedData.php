<?php

declare(strict_types=1);

namespace CrowdSec\RemediationEngine\Tests;

/**
 * Mocked data for unit test.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class MockedData
{
    public const DECISIONS = [
        'new_ip_v4' => [
            'new' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2, ],
            ],
            'deleted' => [],
        ],
        'new_ip_v4_other' => [
            'new' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4, ],
            ],
            'deleted' => [],
        ],
        'new_ip_v4_double' => [
            'new' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'bypass',
                    'value' => Constants::IP_V4_2, ],
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2, ],
            ],
            'deleted' => [],
        ],
        'deleted_ip_v4' => [
            'deleted' => [
                ['duration' => '147h',
                    'origin' => 'CAPI34',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'captcha',
                    'value' => Constants::IP_V4_2,
                ],
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2,
                ],
            ],
            'new' => [],
        ],
        'new_ip_v4_range' => [
            'new' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'range',
                    'type' => 'ban',
                    'value' => Constants::IP_V4 . '/' . Constants::IP_RANGE, ],
            ],
            'deleted' => [],
        ],
        'new_ip_v6_range' => [
            'new' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'range',
                    'type' => 'ban',
                    'value' => Constants::IP_V6 . '/' . Constants::IP_RANGE, ],
            ],
            'deleted' => [],
        ],
        'delete_ip_v4_range' => [
            'deleted' => [
                ['duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'range',
                    'type' => 'ban',
                    'value' => Constants::IP_V4 . '/' . Constants::IP_RANGE, ],
            ],
            'new' => [],
        ],
        'ip_v4_multiple' => [
            'new' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4,
                ],
                [
                    'duration' => '147h',
                    'origin' => 'CAPI2',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4,
                ],
                [
                    'duration' => '147h',
                    'origin' => 'CAPI3',
                    'scenario' => 'manual',
                    'scope' => 'range',
                    'type' => 'ban',
                    'value' => Constants::IP_V4 . '/' . Constants::IP_RANGE,
                ],
                [
                    'duration' => '147h',
                    'origin' => 'CAPI4',
                    'scenario' => 'manual',
                    'scope' => 'range',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2 . '/' . Constants::IP_RANGE,
                ],
                [
                    'duration' => '147h',
                    'origin' => 'CAPI5',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2,
                ],
            ],
            'deleted' => [],
        ],
        'ip_v4_multiple_bis' => [
            'deleted' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI2',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4,
                ],
            ],
            'new' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI5',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2,
                ],
                [
                    'duration' => '147h',
                    'origin' => 'CAPI6',
                    'scenario' => 'manual',
                    'scope' => 'ip',
                    'type' => 'ban',
                    'value' => Constants::IP_V4_2,
                ],
            ],
        ],
        'ip_v4_remove_unknown' => [
            'deleted' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'do-not-know-delete',
                    'type' => 'ban',
                    'value' => Constants::IP_V4,
                ],
            ],
            'new' => [],
        ],
        'ip_v4_store_unknown' => [
            'new' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'do-not-know-store',
                    'type' => 'ban',
                    'value' => Constants::IP_V4,
                ],
            ],
            'deleted' => [],
        ],
        'country_ban' => [
            'new' => [
                [
                    'duration' => '147h',
                    'origin' => 'CAPI',
                    'scenario' => 'manual',
                    'scope' => 'Country',
                    'type' => 'ban',
                    'value' => 'FR',
                ],
            ],
            'deleted' => [],
        ],
    ];

    public const DECISIONS_CAPI_V3 = [
        'new_ip_v4' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_2,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'new_ip_v4_with_0_duration' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '0h',
                            'value' => Constants::IP_V4_2,
                        ],
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'new_ip_v4_and_list' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_2,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
            'links' => [
                'blocklists' => [
                    [
                        'name' => 'tor-exit-nodes',
                        'url' => 'some-url',
                        'remediation' => 'captcha',
                        'scope' => 'ip',
                        'duration' => '24h',
                    ],
                ],
            ],
        ],
        'new_ip_v4_other' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'new_ip_v4_double' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_2,
                        ],
                    ],
                ],
                [
                    'scenario' => 'crowdsecurity/http-sensitive',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_3,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'deleted_ip_v4' => [
            'deleted' => [
                [
                    'scope' => 'ip',
                    'decisions' => [
                        Constants::IP_V4_2,
                    ],
                ],
            ],
            'new' => [],
        ],
        'new_ip_v4_range' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'range',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4 . '/' . Constants::IP_RANGE,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'new_ip_v6_range' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'range',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V6 . '/' . Constants::IP_RANGE,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'delete_ip_v4_range' => [
            'deleted' => [
                [
                    'scope' => 'range',
                    'decisions' => [
                            Constants::IP_V4 . '/' . Constants::IP_RANGE,
                        ],
                ],
            ],
            'new' => [],
        ],
        'ip_v4_multiple' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_2,
                        ],
                    ],
                ],
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'range',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4 . '/' . Constants::IP_RANGE,
                        ],
                    ],
                ],
                [
                    'scenario' => 'crowdsecurity/http-sensitive',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'ip_v4_multiple_bis' => [
            'deleted' => [
                [
                    'scope' => 'ip',
                    'decisions' => [
                            Constants::IP_V4,
                        ],
                ],
            ],
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'ip',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4_3,
                        ],
                    ],
                ],
            ],
        ],
        'ip_v4_remove_unknown' => [
            'deleted' => [
                [
                    'scope' => 'do-not-know-delete',
                    'decisions' => [
                            Constants::IP_V4,
                        ],
                ],
            ],
            'new' => [],
        ],
        'ip_v4_store_unknown' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'do-not-know-store',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => Constants::IP_V4,
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
        'country_ban' => [
            'new' => [
                [
                    'scenario' => 'crowdsecurity/http-backdoors-attempts',
                    'scope' => 'Country',
                    'decisions' => [
                        [
                            'duration' => '147h',
                            'value' => 'FR',
                        ],
                    ],
                ],
            ],
            'deleted' => [],
        ],
    ];
}
