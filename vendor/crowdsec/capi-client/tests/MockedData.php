<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\Tests;

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

    public const LOGIN_SUCCESS = <<<EOT
{"code": 200, "token": "this-is-a-token", "expire": "YYYY-MM-ddThh:mm:ssZ"}
EOT;

    public const METRICS_SUCCESS = <<<EOT
{"code": 200, "message": "metrics updated successfully"}
EOT;

    public const LOGIN_BAD_CREDENTIALS = <<<EOT
{
  "message": "The machine_id or password is incorrect"
}
EOT;

    public const REGISTER_ALREADY = <<<EOT
{
  "message": "User already registered."
}
EOT;

    public const SUCCESS = <<<EOT
{"message":"OK"}
EOT;

    public const BAD_REQUEST = <<<EOT
{
  "message": "Invalid request body",
  "errors": "[Unknown error parsing request body]"
}
EOT;

    public const SIGNALS_BAD_REQUEST = <<<EOT
{
  "message": "Invalid request body",
  "errors": "[object has missing required properties ([\"scenario_hash\"])]"
}
EOT;

    public const UNAUTHORIZED = <<<EOT
{"message":"Unauthorized"}
EOT;

    public const DECISIONS_STREAM_LIST = <<<EOT
{"new": [], "deleted": []}
EOT;
}
