<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests;

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
    public const HTTP_200 = 200;
    public const HTTP_400 = 400;
    public const HTTP_401 = 401;
    public const HTTP_403 = 403;
    public const HTTP_500 = 500;

    public const DECISIONS_STREAM_LIST = <<<EOT
{"new": [], "deleted": []}
EOT;

    public const DECISIONS_FILTER = <<<EOT
[{"duration":"3h59m56.205431304s","id":1,"origin":"cscli","scenario":"manual 'ban' from ''","scope":"Ip","type":"ban","value":"172.26.0.2"}]
EOT;

    public const UNAUTHORIZED = <<<EOT
{"message":"Unauthorized"}
EOT;
}
