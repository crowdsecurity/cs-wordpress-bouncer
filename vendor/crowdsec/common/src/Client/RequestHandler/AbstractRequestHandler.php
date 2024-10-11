<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Constants;

/**
 * Request handler abstraction.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractRequestHandler implements RequestHandlerInterface
{
    /**
     * @var array
     */
    private $configs;

    public function __construct(array $configs = [])
    {
        $this->configs = $configs;
    }

    /**
     * Retrieve a config value by name.
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * Retrieve the appropriate timeout value for the current request.
     * The returned value will be used as milliseconds for AppSec requests and as seconds for API requests.
     */
    protected function getTimeout(Request $request): int
    {
        if ($request instanceof AppSecRequest) {
            return $this->getConfig('appsec_timeout_ms') ?? Constants::APPSEC_TIMEOUT_MS;
        }

        return $this->getConfig('api_timeout') ?? Constants::API_TIMEOUT;
    }

    /**
     * Retrieve the appropriate connect timeout value for the current request.
     */
    protected function getConnectTimeout(Request $request): int
    {
        if ($request instanceof AppSecRequest) {
            return $this->getConfig('appsec_connect_timeout_ms') ?? Constants::APPSEC_CONNECT_TIMEOUT_MS;
        }

        return $this->getConfig('api_connect_timeout') ?? Constants::API_CONNECT_TIMEOUT;
    }
}
