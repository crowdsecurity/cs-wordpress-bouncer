<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\HttpMessage\Request;
use CrowdSec\CapiClient\HttpMessage\Response;
use CrowdSec\CapiClient\RequestHandler\Curl;
use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/**
 * The low level REST Client.
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
    private $allowedMethods = ['POST', 'GET'];
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

    public function __construct(
        array $configs,
        RequestHandlerInterface $requestHandler = null,
        LoggerInterface $logger = null
    ) {
        $this->configs = $configs;
        $this->requestHandler = ($requestHandler) ?: new Curl($this->configs);
        $this->url = $this->configs['api_url'];
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        $this->logger = $logger;
    }

    /**
     * Retrieve a config value by name.
     *
     * @return mixed
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
    }

    public function getUrl(): string
    {
        return rtrim($this->url, '/') . '/';
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
     * @codeCoverageIgnore
     *
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
    private function formatResponseBody(Response $response): array
    {
        $statusCode = $response->getStatusCode();

        $body = $response->getJsonBody();
        $decoded = [];
        if (!empty($body) && 'null' !== $body) {
            $decoded = json_decode($response->getJsonBody(), true);

            if (null === $decoded) {
                $message = 'Body response is not a valid json';
                $this->logger->error($message, ['type' => 'CLIENT_FORMAT_RESPONSE']);
                throw new ClientException($message);
            }
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            $message = "Unexpected response status code: $statusCode. Body was: " . str_replace("\n", '', $body);
            if ($statusCode !== 404) {
                throw new ClientException($message, $statusCode);
            }
        }

        return $decoded;
    }

    private function getFullUrl(string $endpoint): string
    {
        return $this->getUrl() . ltrim($endpoint, '/');
    }
}
