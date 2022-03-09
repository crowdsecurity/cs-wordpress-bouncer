<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

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
class RestClient
{
    /** @var string */
    private $headerString = null;

    /** @var int */
    private $timeout = null;

    /** @var string */
    private $baseUri = null;

    /** @var LoggerInterface */
    private $logger = null;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Configure this instance.
     */
    public function configure(string $baseUri, array $headers, int $timeout): void
    {
        $this->baseUri = $baseUri;
        $this->headerString = $this->convertHeadersToString($headers);
        $this->timeout = $timeout;

        $this->logger->debug('', [
            'type' => 'REST_CLIENT_INIT',
            'base_uri' => $this->baseUri,
            'timeout' => $this->timeout,
        ]);
    }

    /**
     * Convert an key-value array of headers to the official HTTP header string.
     */
    private function convertHeadersToString(array $headers): string
    {
        $builtHeaderString = '';
        foreach ($headers as $key => $value) {
            $builtHeaderString .= "$key: $value\r\n";
        }

        return $builtHeaderString;
    }

    /**
     * Send an HTTP request using the file_get_contents and parse its JSON result if any.
     *
     * @throws BouncerException when the reponse status is not 2xx
     */
    public function request(
        string $endpoint,
        array $queryParams = null,
        array $bodyParams = null,
        string $method = 'GET',
        array $headers = null,
        int $timeout = null
    ): ?array {
        if ($queryParams) {
            $endpoint .= '?'.http_build_query($queryParams);
        }
        $header = $headers ? $this->convertHeadersToString($headers) : $this->headerString;
        $config = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'timeout' => $timeout ?: $this->timeout,
                'ignore_errors' => true,
            ],
        ];
        if ($bodyParams) {
            $config['http']['content'] = json_encode($bodyParams);
        }
        $context = stream_context_create($config);

        $this->logger->debug('', [
            'type' => 'HTTP CALL',
            'method' => $method,
            'uri' => $this->baseUri.$endpoint,
            'content' => 'POST' === $method ? $config['http']['content'] : null,
            //'header' => $header, # Do not display header to avoid logging sensible data
        ]);

        $response = file_get_contents($this->baseUri.$endpoint, false, $context);
        if (false === $response) {
            throw new BouncerException('Unexpected HTTP call failure.');
        }
        $parts = explode(' ', $http_response_header[0]);
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) ($parts[1]);
        }

        if ($status < 200 || $status >= 300) {
            throw new BouncerException("unexpected response status from $this->baseUri$endpoint: $status\n".$response);
        }
        $data = json_decode($response, true);

        return $data;
    }
}
