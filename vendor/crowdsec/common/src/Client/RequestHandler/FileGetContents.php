<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Constants;

/**
 * File_get_contents request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class FileGetContents extends AbstractRequestHandler
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request): Response
    {
        $config = $this->createContextConfig($request);
        $context = stream_context_create($config);

        $method = $request->getMethod();
        $parameters = $request->getParams();
        $url = $request->getUri();

        if ('GET' === strtoupper($method) && !empty($parameters)) {
            $url .= strpos($url, '?') ? '&' : '?';
            $url .= http_build_query($parameters);
        }

        $fullResponse = $this->exec($url, $context);
        $responseBody = (isset($fullResponse['response'])) ? $fullResponse['response'] : false;
        if (false === $responseBody) {
            throw new ClientException('Unexpected HTTP call failure.', 500);
        }
        $responseHeaders = (isset($fullResponse['header'])) ? $fullResponse['header'] : [];
        $parts = !empty($responseHeaders) ? explode(' ', $responseHeaders[0]) : [];
        $status = $this->getResponseHttpCode($parts);

        return new Response($responseBody, $status);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param resource $context
     */
    protected function exec(string $url, $context): array
    {
        return ['response' => file_get_contents($url, false, $context), 'header' => $http_response_header];
    }

    /**
     * @param string[] $parts
     *
     * @psalm-param list<string> $parts
     */
    protected function getResponseHttpCode(array $parts): int
    {
        $status = 0;
        if (\count($parts) > 1) {
            $status = (int) $parts[1];
        }

        return $status;
    }

    /**
     * Convert a key-value array of headers to the official HTTP header string.
     */
    protected function convertHeadersToString(array $headers): string
    {
        $builtHeaderString = '';
        foreach ($headers as $key => $value) {
            $builtHeaderString .= "$key: $value\r\n";
        }

        return $builtHeaderString;
    }

    /**
     * Retrieve configuration for the stream content.
     *
     * @return array|array[]
     *
     * @throws ClientException
     */
    private function createContextConfig(Request $request): array
    {
        $headers = $request->getHeaders();
        if (!isset($headers['User-Agent'])) {
            throw new ClientException('User agent is required', 400);
        }
        $header = $this->convertHeadersToString($headers);
        $method = $request->getMethod();
        $timeout = $this->getConfig('api_timeout');
        // Negative value will result in an unlimited timeout
        $timeout = is_null($timeout) ? Constants::API_TIMEOUT : $timeout;
        $config = [
            'http' => [
                'method' => $method,
                'header' => $header,
                'ignore_errors' => true,
                'timeout' => $timeout,
            ],
        ];

        $config['ssl'] = ['verify_peer' => false];
        $authType = $this->getConfig('auth_type');
        if ($authType && Constants::AUTH_TLS === $authType) {
            $verifyPeer = $this->getConfig('tls_verify_peer') ?? true;
            $config['ssl'] = [
                'verify_peer' => $verifyPeer,
                'local_cert' => $this->getConfig('tls_cert_path') ?? '',
                'local_pk' => $this->getConfig('tls_key_path') ?? '',
            ];
            if ($verifyPeer) {
                $config['ssl']['cafile'] = $this->getConfig('tls_ca_cert_path') ?? '';
            }
        }

        if ('POST' === strtoupper($method)) {
            $config['http']['content'] = json_encode($request->getParams());
        }

        return $config;
    }
}
