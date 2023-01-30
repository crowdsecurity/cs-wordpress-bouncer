<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\HttpMessage;

/**
 * HTTP messages consist of requests from a client to CAPI and responses
 * from CAPI to a client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractMessage
{
    /**
     * @var array
     */
    protected $headers = [];

    public function getHeaders(): array
    {
        return $this->headers;
    }
}
