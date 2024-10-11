<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\TimeoutException;

/**
 * Request handler interface.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
interface RequestHandlerInterface
{
    /**
     * Performs an HTTP request and returns a response.
     *
     * @throws ClientException
     * @throws TimeoutException
     */
    public function handle(Request $request): Response;
}
