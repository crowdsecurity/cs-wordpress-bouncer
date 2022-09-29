<?php

declare(strict_types=1);

namespace CrowdSecBouncer\RestClient;

use CrowdSecBouncer\BouncerException;
use CrowdSecBouncer\Constants;
use Psr\Log\LoggerInterface;

class FileGetContents extends AbstractClient
{
    /** @var string|null */
    private $headerString;

    public function __construct(array $configs, LoggerInterface $logger)
    {
        parent::__construct($configs, $logger);
        $this->headerString = $this->convertHeadersToString($this->headers);
    }

    /**
     * Send an HTTP request using the file_get_contents and parse its JSON result if any.
     *
     * @throws BouncerException
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
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $config = $this->createConfig($bodyParams, $method, $headers, $timeout);
        $context = stream_context_create($config);

        $this->logger->debug('', [
            'type' => 'HTTP CALL',
            'method' => $method,
            'uri' => $this->baseUri . $endpoint,
            'content' => 'POST' === $method ? $config['http']['content'] ?? null : null,
            // 'header' => $header, # Do not display header to avoid logging sensible data
        ]);

        $response = file_get_contents($this->baseUri . $endpoint, false, $context);
        if (false === $response) {
            throw new BouncerException('Unexpected HTTP call failure.');
        }
        $parts = explode(' ', $http_response_header[0]);
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) $parts[1];
        }

        if ($status < 200 || $status >= 300) {
            $message = "Unexpected response status from $this->baseUri$endpoint: $status\n" . $response;
            throw new BouncerException($message);
        }

        return json_decode($response, true);
    }

    /**
     * Convert a key-value array of headers to the official HTTP header string.
     */
    private function convertHeadersToString(array $headers): string
    {
        $builtHeaderString = '';
        foreach ($headers as $key => $value) {
            $builtHeaderString .= "$key: $value\r\n";
        }

        return $builtHeaderString;
    }

    private function createConfig(
        array $bodyParams = null,
        string $method = 'GET',
        array $headers = null,
        int $timeout = null
    ): array {
        $header = $headers ? $this->convertHeadersToString($headers) : $this->headerString;
        $config = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'timeout' => $timeout ?: $this->timeout,
                'ignore_errors' => true,
            ],
        ];
        $config['ssl'] = ['verify_peer' => false];
        if (isset($this->configs['auth_type']) && Constants::AUTH_TLS === $this->configs['auth_type']) {
            $verifyPeer = $this->configs['tls_verify_peer'] ?? true;
            $config['ssl'] = [
                'verify_peer' => $verifyPeer,
                'local_cert' => $this->configs['tls_cert_path'] ?? '',
                'local_pk' => $this->configs['tls_key_path'] ?? '',
            ];
            if ($verifyPeer) {
                $config['ssl']['cafile'] = $this->configs['tls_ca_cert_path'] ?? '';
            }
        }

        if ($bodyParams) {
            $config['http']['content'] = json_encode($bodyParams);
        }

        return $config;
    }
}
