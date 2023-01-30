<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient\HttpMessage;

/**
 * Request that will be sent to CAPI.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Request extends AbstractMessage
{
    /**
     * @var array
     */
    protected $headers = [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ];
    /**
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var string
     */
    private $uri;

    public function __construct(
        string $uri,
        string $method,
        array $headers = [],
        array $parameters = []
    ) {
        $this->uri = $uri;
        $this->method = $method;
        $this->headers = array_merge($this->headers, $headers);
        $this->parameters = $parameters;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParams(): array
    {
        return $this->parameters;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
