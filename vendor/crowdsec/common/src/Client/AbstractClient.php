<?php

declare(strict_types=1);

namespace CrowdSec\Common\Client;

use CrowdSec\Common\Client\HttpMessage\AppSecRequest;
use CrowdSec\Common\Client\HttpMessage\Request;
use CrowdSec\Common\Client\HttpMessage\Response;
use CrowdSec\Common\Client\RequestHandler\Curl;
use CrowdSec\Common\Client\RequestHandler\RequestHandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * The low level CrowdSec REST Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractClient
{
    /**
     * @var array
     */
    protected $configs = [];
    /**
     * @var string[]
     */
    private $allowedMethods = ['POST', 'GET', 'DELETE'];
    /**
     * @var string[]
     */
    private $allowedAppSecMethods = ['POST', 'GET'];
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;
    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $appSecUrl;

    public function __construct(
        array $configs,
        ?RequestHandlerInterface $requestHandler = null,
        ?LoggerInterface $logger = null
    ) {
        $this->configs = $configs;
        $this->requestHandler = ($requestHandler) ?: new Curl($this->configs);
        $this->url = $this->getConfig('api_url');
        $this->appSecUrl = $this->getConfig('appsec_url');
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        $this->logger = $logger;
        $this->logger->debug('Instantiate client', [
            'type' => 'CLIENT_INIT',
            'configs' => array_merge($configs, ['api_key' => '***']),
        ]);
    }

    /**
     * Retrieve a config value by name.
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->requestHandler;
    }

    public function getUrl(): string
    {
        return rtrim($this->url, '/') . '/';
    }

    public function getAppSecUrl(): string
    {
        return rtrim($this->appSecUrl, '/') . '/';
    }

    /**
     * Performs an HTTP request (POST, GET, ...) and returns its response body as an array.
     *
     * @throws ClientException
     */
    protected function request(
        string $method,
        string $endpoint,
        array $parameters = [],
        array $headers = []
    ): array {
        $method = strtoupper($method);
        if (!in_array($method, $this->allowedMethods)) {
            $message = "Method ($method) is not allowed.";
            $this->logger->error($message, ['type' => 'CLIENT_REQUEST']);
            throw new ClientException($message);
        }

        $response = $this->sendRequest(
            new Request($this->getFullUrl($endpoint), $method, $headers, $parameters)
        );

        return $this->formatResponseBody($response);
    }

    /**
     * Performs an HTTP request (POST, GET) to AppSec and returns its response body as an array.
     *
     * @throws ClientException
     */
    protected function requestAppSec(
        string $method,
        array $headers = [],
        string $rawBody = ''
    ): array {
        $method = strtoupper($method);
        if (!in_array($method, $this->allowedAppSecMethods)) {
            $message = "Method ($method) is not allowed.";
            $this->logger->error($message, ['type' => 'CLIENT_APPSEC_REQUEST']);
            throw new ClientException($message);
        }

        $response = $this->sendRequest(
            new AppSecRequest($this->getAppSecUrl(), $method, $headers, $rawBody)
        );

        return $this->formatResponseBody($response, ['403']);
    }

    /**
     * @throws ClientException
     */
    private function sendRequest(Request $request): Response
    {
        return $this->requestHandler->handle($request);
    }

    /**
     * Verify the response and return an array.
     *
     * @throws ClientException
     */
    private function formatResponseBody(Response $response, array $mutedCodes = ['404']): array
    {
        $statusCode = $response->getStatusCode();
        $body = $response->getJsonBody();
        if ($statusCode < 200 || $statusCode >= 300) {
            $message = "Unexpected response status code: $statusCode. Body was: " . str_replace("\n", '', $body);
            if (!in_array($statusCode, $mutedCodes)) {
                throw new ClientException($message, $statusCode);
            }
        }
        $decoded = [];
        if (!empty($body) && 'null' !== $body) {
            $decoded = json_decode($response->getJsonBody(), true);

            if (null === $decoded) {
                $message = 'Body response is not a valid json';
                $this->logger->error($message, ['type' => 'CLIENT_FORMAT_RESPONSE']);
                throw new ClientException($message);
            }
        }

        return $decoded;
    }

    private function getFullUrl(string $endpoint): string
    {
        return $this->getUrl() . ltrim($endpoint, '/');
    }
}
