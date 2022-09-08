<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use Psr\Log\LoggerInterface;
use CrowdSecBouncer\RestClient\FileGetContents;
use CrowdSecBouncer\RestClient\Curl;
use CrowdSecBouncer\RestClient\AbstractClient;

/**
 * The LAPI REST Client. This is used to retrieve decisions.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class ApiClient
{
    /** @var LoggerInterface */
    private $logger;
    /** @var array */
    private $configs;

    /**
     * @var AbstractClient
     */
    private $restClient;

    /**
     * @param array $configs
     * @param LoggerInterface $logger
     * @throws BouncerException
     */
    public function __construct(array $configs, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->configs = $configs;
        $useCurl = !empty($this->configs['use_curl']);
        if (empty($this->configs['api_user_agent'])) {
            throw new BouncerException('User agent is required');
        }
        $userAgent = $this->configs['api_user_agent'];
        $this->configs['headers'] = [
            'User-Agent' => $this->configs['api_user_agent'],
            'Accept' => 'application/json',
        ];
        if (!empty($this->configs['api_key'])) {
            $this->configs['headers']['X-Api-Key'] = $this->configs['api_key'];
        }

        $this->restClient = $useCurl ?
            new Curl($this->configs, $this->logger) :
            new FileGetContents($this->configs, $this->logger);

        $this->logger->debug('', [
            'type' => 'API_CLIENT_INIT',
            'user_agent' => $userAgent,
            'rest_client' => \get_class($this->restClient)
        ]);
    }

    /**
     * Request decisions using the specified $filter array.
     * @throws BouncerException
     */
    public function getFilteredDecisions(array $filter): array
    {
        return $this->restClient->request('/v1/decisions', $filter) ?: [];
    }

    /**
     * Request decisions using the stream mode. When the $startup flag is used, all the decisions are returned.
     * Else only the decisions updates (add or remove) from the last stream call are returned.
     * @throws BouncerException
     */
    public function getStreamedDecisions(
        bool $startup = false,
        array $scopes = [Constants::SCOPE_IP, Constants::SCOPE_RANGE]
    ): array {
        /** @var array */
        return $this->restClient->request(
            '/v1/decisions/stream',
            ['startup' => $startup ? 'true' : 'false', 'scopes' => implode(',', $scopes)]
        );
    }

    public function getRestClient(): AbstractClient
    {
        return $this->restClient;
    }
}
