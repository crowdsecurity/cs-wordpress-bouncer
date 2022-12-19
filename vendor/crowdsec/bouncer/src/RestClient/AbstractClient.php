<?php

declare(strict_types=1);

namespace CrowdSecBouncer\RestClient;

use CrowdSecBouncer\BouncerException;
use CrowdSecBouncer\Constants;
use Psr\Log\LoggerInterface;

/**
 * The low level REST Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient
{
    /** @var mixed|null */
    protected $baseUri = null;
    /** @var array */
    protected $configs;
    /** @var array */
    protected $headers = [];

    /** @var LoggerInterface */
    protected $logger;
    /** @var int|mixed|null */
    protected $timeout = null;

    /**
     * @throws BouncerException
     */
    public function __construct(array $configs, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->configs = $configs;
        if (empty($this->configs['api_url'])) {
            throw new BouncerException('Api url is required');
        }
        $this->baseUri = $this->configs['api_url'];
        $this->timeout = $this->configs['api_timeout'] ?? Constants::API_TIMEOUT;
        if (empty($this->configs['headers'])) {
            throw new BouncerException('Headers are required');
        }
        $this->headers = $this->configs['headers'];
        if (empty($this->headers['User-Agent'])) {
            throw new BouncerException('User-Agent header required');
        }

        $this->logger->debug('', [
            'type' => 'REST_CLIENT_INIT',
            'base_uri' => $this->baseUri,
            'timeout' => $this->timeout,
            'user_agent' => $this->headers['User-Agent'],
        ]);
    }

    /**
     * Send an HTTP request and parse its JSON result if any.
     */
    abstract public function request(
        string $endpoint,
        array $queryParams = null,
        array $bodyParams = null,
        string $method = 'GET',
        array $headers = null
    ): ?array;
}
