<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\RequestHandler;

use CrowdSec\LapiClient\ClientException;
use CrowdSec\LapiClient\Constants;
use CrowdSec\LapiClient\HttpMessage\Request;
use CrowdSec\LapiClient\HttpMessage\Response;

/**
 * Curl request handler.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Curl extends AbstractRequestHandler implements RequestHandlerInterface
{
    /**
     * {@inheritdoc}
     *
     * @throws ClientException
     */
    public function handle(Request $request): Response
    {
        $handle = curl_init();

        $curlOptions = $this->createOptions($request);
        curl_setopt_array($handle, $curlOptions);

        $response = $this->exec($handle);

        if (false === $response) {
            throw new ClientException('Unexpected CURL call failure: ' . curl_error($handle), 500);
        }

        $statusCode = $this->getResponseHttpCode($handle);
        if (empty($statusCode)) {
            throw new ClientException('Unexpected empty response http code');
        }

        curl_close($handle);

        return new Response((string) $response, $statusCode);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param mixed $handle
     *
     * @return bool|string
     */
    protected function exec($handle)
    {
        return curl_exec($handle);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param mixed $handle
     *
     * @return mixed
     */
    protected function getResponseHttpCode($handle)
    {
        return curl_getinfo($handle, \CURLINFO_HTTP_CODE);
    }

    private function handleConfigs(): array
    {
        $result = [\CURLOPT_SSL_VERIFYPEER => false];
        $authType = $this->getConfig('auth_type');
        if ($authType && Constants::AUTH_TLS === $authType) {
            $verifyPeer = $this->getConfig('tls_verify_peer') ?? true;
            $result[\CURLOPT_SSL_VERIFYPEER] = $verifyPeer;
            //   The --cert option
            $result[\CURLOPT_SSLCERT] = $this->getConfig('tls_cert_path') ?? '';
            // The --key option
            $result[\CURLOPT_SSLKEY] = $this->getConfig('tls_key_path') ?? '';
            if ($verifyPeer) {
                // The --cacert option
                $result[\CURLOPT_CAINFO] = $this->getConfig('tls_ca_cert_path') ?? '';
            }
        }
        $timeout = $this->getConfig('api_timeout') ?? Constants::API_TIMEOUT;
        if ($timeout > 0) {
            $result[\CURLOPT_TIMEOUT] = $timeout;
        }

        return $result;
    }

    private function handleMethod(string $method, string $url, array $parameters = []): array
    {
        $result = [];
        if ('POST' === strtoupper($method)) {
            $result[\CURLOPT_POST] = true;
            $result[\CURLOPT_CUSTOMREQUEST] = 'POST';
            $result[\CURLOPT_POSTFIELDS] = json_encode($parameters);
        } elseif ('GET' === strtoupper($method)) {
            $result[\CURLOPT_POST] = false;
            $result[\CURLOPT_CUSTOMREQUEST] = 'GET';
            $result[\CURLOPT_HTTPGET] = true;

            if (!empty($parameters)) {
                $url .= strpos($url, '?') ? '&' : '?';
                $url .= http_build_query($parameters);
            }
        } elseif ('DELETE' === strtoupper($method)) {
            $result[\CURLOPT_POST] = false;
            $result[\CURLOPT_CUSTOMREQUEST] = 'DELETE';
        }
        $result[\CURLOPT_URL] = $url;

        return $result;
    }

    /**
     * Retrieve Curl options.
     *
     * @throws ClientException
     */
    private function createOptions(Request $request): array
    {
        $headers = $request->getHeaders();
        $method = $request->getMethod();
        $url = $request->getUri();
        $parameters = $request->getParams();
        if (!isset($headers['User-Agent'])) {
            throw new ClientException('User agent is required', 400);
        }
        $options = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_USERAGENT => $headers['User-Agent'],
            \CURLOPT_ENCODING => ''
        ];

        $options[\CURLOPT_HTTPHEADER] = [];
        foreach ($headers as $key => $values) {
            foreach (\is_array($values) ? $values : [$values] as $value) {
                $options[\CURLOPT_HTTPHEADER][] = sprintf('%s:%s', $key, $value);
            }
        }
        // We need to keep keys indexes (array_merge not keeping indexes)
        $options += $this->handleConfigs();
        $options += $this->handleMethod($method, $url, $parameters);

        return $options;
    }
}
