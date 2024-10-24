<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient;

use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\Common\Client\ClientException as CommonClientException;
use CrowdSec\Common\Client\RequestHandler\RequestHandlerInterface;
use CrowdSec\Common\Client\TimeoutException as CommonTimeoutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * The Bouncer Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Bouncer extends AbstractClient
{
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var array
     */
    private $headers;

    public function __construct(
        array $configs,
        ?RequestHandlerInterface $requestHandler = null,
        ?LoggerInterface $logger = null
    ) {
        $this->configure($configs);
        $this->headers = [Constants::HEADER_LAPI_USER_AGENT => $this->formatUserAgent($this->configs)];
        if (!empty($this->configs['api_key'])) {
            $this->headers[Constants::HEADER_LAPI_API_KEY] = $this->configs['api_key'];
        }
        parent::__construct($this->configs, $requestHandler, $logger);
    }

    /**
     * Process a call to AppSec component.
     *
     * @see https://docs.crowdsec.net/docs/appsec/protocol
     *
     * @throws ClientException
     */
    public function getAppSecDecision(array $headers, string $rawBody = ''): array
    {
        $method = $rawBody ? 'POST' : 'GET';

        return $this->manageAppSecRequest(
            $method,
            $headers,
            $rawBody
        );
    }

    /**
     * Process a decisions call to LAPI with some filter(s).
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/bouncers/getDecisions
     *
     * @throws ClientException
     */
    public function getFilteredDecisions(array $filter = []): array
    {
        return $this->manageRequest(
            'GET',
            Constants::DECISIONS_FILTER_ENDPOINT,
            $filter
        );
    }

    /**
     * Process a decisions stream call to LAPI.
     * When the $startup flag is used, all the decisions are returned.
     * Else only the decisions updates (add or remove) from the last stream call are returned.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=LAPI#/bouncers/getDecisionsStream
     *
     * @throws ClientException
     */
    public function getStreamDecisions(
        bool $startup,
        array $filter = []
    ): array {
        return $this->manageRequest(
            'GET',
            Constants::DECISIONS_STREAM_ENDPOINT,
            array_merge(['startup' => $startup ? 'true' : 'false'], $filter)
        );
    }

    private function cleanHeadersForLog(array $headers): array
    {
        $cleanedHeaders = $headers;
        if (array_key_exists(Constants::HEADER_APPSEC_API_KEY, $cleanedHeaders)) {
            $cleanedHeaders[Constants::HEADER_APPSEC_API_KEY] = '***';
        }

        return $cleanedHeaders;
    }

    private function cleanRawBodyForLog(string $rawBody, int $maxLength): string
    {
        return strlen($rawBody) > $maxLength ? substr($rawBody, 0, $maxLength) . '...[TRUNCATED]' : $rawBody;
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }

    /**
     * Format User-Agent header. <PHP LAPI client prefix>_<custom suffix>/<vX.Y.Z>.
     */
    private function formatUserAgent(array $configs = []): string
    {
        $userAgentSuffix = !empty($configs['user_agent_suffix']) ? '_' . $configs['user_agent_suffix'] : '';
        $userAgentVersion =
            !empty($configs['user_agent_version']) ? $configs['user_agent_version'] : Constants::VERSION;

        return Constants::USER_AGENT_PREFIX . $userAgentSuffix . '/' . $userAgentVersion;
    }

    /**
     * Make a request to the AppSec component of LAPI.
     *
     * @throws ClientException
     */
    private function manageAppSecRequest(
        string $method,
        array $headers = [],
        string $rawBody = ''
    ): array {
        try {
            $this->logger->debug('Now processing a bouncer AppSec request', [
                'type' => 'BOUNCER_CLIENT_APPSEC_REQUEST',
                'method' => $method,
                'raw body' => $this->cleanRawBodyForLog($rawBody, 200),
                'raw body length' => strlen($rawBody),
                'headers' => $this->cleanHeadersForLog($headers),
            ]);

            return $this->requestAppSec($method, $headers, $rawBody);
        } catch (CommonTimeoutException $e) {
            throw new TimeoutException($e->getMessage(), $e->getCode(), $e);
        } catch (CommonClientException $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Make a request to LAPI.
     *
     * @throws ClientException
     */
    private function manageRequest(
        string $method,
        string $endpoint,
        array $parameters = []
    ): array {
        try {
            $this->logger->debug('Now processing a bouncer request', [
                'type' => 'BOUNCER_CLIENT_REQUEST',
                'method' => $method,
                'endpoint' => $endpoint,
                'parameters' => $parameters,
            ]);

            return $this->request($method, $endpoint, $parameters, $this->headers);
        } catch (CommonTimeoutException $e) {
            throw new TimeoutException($e->getMessage(), $e->getCode(), $e);
        } catch (CommonClientException $e) {
            throw new ClientException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
