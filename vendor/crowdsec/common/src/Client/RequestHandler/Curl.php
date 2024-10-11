<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client\RequestHandler;

use CrowdSec\Common\Client\ClientException;
use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\TimeoutException;
use CrowdSec\Common\Constants;

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
class Curl extends AbstractRequestHandler
{
    /**
     * @throws ClientException
     * @throws TimeoutException
     */
    public function handle(Request $request): Response
    {
        $handle = curl_init();

        $curlOptions = $this->createOptions($request);
        curl_setopt_array($handle, $curlOptions);

        $response = $this->exec($handle);

        if (false === $response) {
            $errorCode = $this->errno($handle);
            $errorMessage = $this->error($handle);
            if (\CURLE_OPERATION_TIMEOUTED === $errorCode) {
                throw new TimeoutException('CURL call timeout: ' . $errorMessage, 500);
            }
            throw new ClientException('Unexpected CURL call failure: ' . $errorMessage, 500);
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
     */
    protected function errno($handle): int
    {
        return curl_errno($handle);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function error($handle): string
    {
        return curl_error($handle);
    }

    /**
     * @codeCoverageIgnore
     *
     * @return bool|string
     */
    protected function exec($handle)
    {
        return curl_exec($handle);
    }

    /**
     * @codeCoverageIgnore
     */
    protected function getResponseHttpCode($handle)
    {
        return curl_getinfo($handle, \CURLINFO_HTTP_CODE);
    }

    /**
     * Retrieve Curl options.
     *
     * @throws ClientException
     */
    private function createOptions(Request $request): array
    {
        $headers = $request->getValidatedHeaders();
        $method = $request->getMethod();
        $url = $request->getUri();
        $parameters = $request->getParams();
        $rawBody = $request instanceof AppSecRequest ? $request->getRawBody() : '';
        $options = [
            \CURLOPT_HEADER => false,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_ENCODING => '',
        ];
        if (isset($headers['User-Agent'])) {
            $options[\CURLOPT_USERAGENT] = $headers['User-Agent'];
        }

        $options[\CURLOPT_HTTPHEADER] = [];
        foreach ($headers as $key => $values) {
            foreach (\is_array($values) ? $values : [$values] as $value) {
                $options[\CURLOPT_HTTPHEADER][] = sprintf('%s:%s', $key, $value);
            }
        }
        // We need to keep keys indexes (array_merge not keeping indexes)
        $options += $this->handleSSL($request);
        $options += $this->handleTimeout($request);
        $options += $this->handleMethod($method, $url, $parameters, $rawBody);

        return $options;
    }

    private function getConnectTimeoutOption(Request $request): int
    {
        return $request instanceof AppSecRequest ? \CURLOPT_CONNECTTIMEOUT_MS : \CURLOPT_CONNECTTIMEOUT;
    }

    private function getTimeoutOption(Request $request): int
    {
        return $request instanceof AppSecRequest ? \CURLOPT_TIMEOUT_MS : \CURLOPT_TIMEOUT;
    }

    private function handleMethod(string $method, string $url, array $parameters = [], string $rawBody = ''): array
    {
        $result = [];
        if ('POST' === strtoupper($method)) {
            $result[\CURLOPT_POST] = true;
            $result[\CURLOPT_CUSTOMREQUEST] = 'POST';
            $result[\CURLOPT_POSTFIELDS] = $rawBody ?: json_encode($parameters);
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

    private function handleSSL(Request $request): array
    {
        $result = [\CURLOPT_SSL_VERIFYPEER => false];
        if ($request instanceof AppSecRequest) {
            /**
             * AppSec does not currently support TLS authentication.
             *
             * @see https://github.com/crowdsecurity/crowdsec/issues/3172
             */
            return $result;
        }

        $authType = $this->getConfig('auth_type');
        if ($authType && Constants::AUTH_TLS === $authType) {
            $verifyPeer = $this->getConfig('tls_verify_peer') ?? true;
            $result[\CURLOPT_SSL_VERIFYPEER] = $verifyPeer;
            // The --cert option
            $result[\CURLOPT_SSLCERT] = $this->getConfig('tls_cert_path') ?? '';
            // The --key option
            $result[\CURLOPT_SSLKEY] = $this->getConfig('tls_key_path') ?? '';
            if ($verifyPeer) {
                // The --cacert option
                $result[\CURLOPT_CAINFO] = $this->getConfig('tls_ca_cert_path') ?? '';
            }
        }

        return $result;
    }

    private function handleTimeout(Request $request): array
    {
        $result = [];
        $timeout = $this->getTimeout($request);
        /**
         * To obtain an unlimited timeout (with non-positive value),
         * we don't pass the option (as unlimited timeout is the default behavior).
         *
         * @see https://curl.se/libcurl/c/CURLOPT_TIMEOUT.html
         */
        if ($timeout > 0) {
            $result[$this->getTimeoutOption($request)] = $timeout;
        }
        $connectTimeout = $this->getConnectTimeout($request);
        if ($connectTimeout >= 0) {
            /**
             * 0 means infinite timeout (@see https://www.php.net/manual/en/function.curl-setopt.php.
             *
             * @see https://curl.se/libcurl/c/CURLOPT_CONNECTTIMEOUT.html
             */
            $result[$this->getConnectTimeoutOption($request)] = $connectTimeout;
        }

        return $result;
    }
}
