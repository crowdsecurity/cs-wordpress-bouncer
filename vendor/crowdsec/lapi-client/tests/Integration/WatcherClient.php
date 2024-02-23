<?php

declare(strict_types=1);

namespace CrowdSec\LapiClient\Tests\Integration;

use CrowdSec\Common\Client\AbstractClient;
use CrowdSec\LapiClient\ClientException;
use CrowdSec\LapiClient\Constants;

class WatcherClient extends AbstractClient
{
    public const WATCHER_LOGIN_ENDPOINT = '/v1/watchers/login';

    public const WATCHER_DECISIONS_ENDPOINT = '/v1/decisions';

    public const WATCHER_ALERT_ENDPOINT = '/v1/alerts';

    public const HOURS24 = '+24 hours';

    /** @var string */
    private $token;
    /**
     * @var array|string[]
     */
    protected $headers = [];

    public function __construct(array $configs)
    {
        $this->configs = $configs;
        $this->headers = ['User-Agent' => 'LAPI_WATCHER_TEST/' . Constants::VERSION];
        $agentTlsPath = getenv('AGENT_TLS_PATH');
        if (!$agentTlsPath) {
            throw new \Exception('Using TLS auth for agent is required. Please set AGENT_TLS_PATH env.');
        }
        $this->configs['auth_type'] = Constants::AUTH_TLS;
        $this->configs['tls_cert_path'] = $agentTlsPath . '/agent.pem';
        $this->configs['tls_key_path'] = $agentTlsPath . '/agent-key.pem';
        $this->configs['tls_verify_peer'] = false;

        parent::__construct($this->configs);
    }

    /**
     * Make a request.
     *
     * @throws ClientException
     */
    private function manageRequest(
        string $method,
        string $endpoint,
        array $parameters = []
    ): array {
        $this->logger->debug('', [
            'type' => 'WATCHER_CLIENT_REQUEST',
            'method' => $method,
            'endpoint' => $endpoint,
            'parameters' => $parameters,
        ]);

        return $this->request($method, $endpoint, $parameters, $this->headers);
    }

    /** Set the initial watcher state */
    public function setInitialState(): void
    {
        $this->deleteAllDecisions();
        $now = new \DateTime();
        $this->addDecision($now, '12h', '+12 hours', TestHelpers::BAD_IP, 'captcha');
        $this->addDecision($now, '24h', self::HOURS24, TestHelpers::BAD_IP . '/' . TestHelpers::IP_RANGE, 'ban');
        $this->addDecision($now, '24h', '+24 hours', TestHelpers::JAPAN, 'captcha', Constants::SCOPE_COUNTRY);
    }

    /** Set the second watcher state */
    public function setSecondState(): void
    {
        $this->logger->info('', ['message' => 'Set "second" state']);
        $this->deleteAllDecisions();
        $now = new \DateTime();
        $this->addDecision($now, '36h', '+36 hours', TestHelpers::NEWLY_BAD_IP, 'ban');
        $this->addDecision(
            $now,
            '48h',
            '+48 hours',
            TestHelpers::NEWLY_BAD_IP . '/' . TestHelpers::IP_RANGE,
            'captcha'
        );
        $this->addDecision($now, '24h', self::HOURS24, TestHelpers::JAPAN, 'captcha', Constants::SCOPE_COUNTRY);
        $this->addDecision($now, '24h', self::HOURS24, TestHelpers::IP_JAPAN, 'ban');
        $this->addDecision($now, '24h', self::HOURS24, TestHelpers::IP_FRANCE, 'ban');
    }

    /**
     * Ensure we retrieved a JWT to connect the API.
     */
    private function ensureLogin(): void
    {
        if (!$this->token) {
            $data = [
               'scenarios' => [],
            ];
            $credentials = $this->manageRequest(
                'POST',
                self::WATCHER_LOGIN_ENDPOINT,
                $data
            );

            $this->token = $credentials['token'];
            $this->headers['Authorization'] = 'Bearer ' . $this->token;
        }
    }

    public function deleteAllDecisions(): void
    {
        // Delete all existing decisions.
        $this->ensureLogin();

        $this->manageRequest(
            'DELETE',
            self::WATCHER_DECISIONS_ENDPOINT,
            []
        );
    }

    protected function getFinalScope($scope, $value)
    {
        $scope = (Constants::SCOPE_IP === $scope && 2 === count(explode('/', $value))) ? Constants::SCOPE_RANGE :
            $scope;

        /**
         * Must use capital first letter as the crowdsec agent seems to query with first capital letter
         * during getStreamDecisions.
         *
         * @see https://github.com/crowdsecurity/crowdsec/blob/ae6bf3949578a5f3aa8ec415e452f15b404ba5af/pkg/database/decisions.go#L56
         */
        return ucfirst($scope);
    }

    public function addDecision(
        \DateTime $now,
        string $durationString,
        string $dateTimeDurationString,
        string $value,
        string $type,
        string $scope = Constants::SCOPE_IP
    ) {
        $stopAt = (clone $now)->modify($dateTimeDurationString)->format('Y-m-d\TH:i:s.000\Z');
        $startAt = $now->format('Y-m-d\TH:i:s.000\Z');

        $body = [
            'capacity' => 0,
            'decisions' => [
                [
                    'duration' => $durationString,
                    'origin' => 'cscli',
                    'scenario' => $type . ' for scope/value (' . $scope . '/' . $value . ') for '
                                  . $durationString . ' for PHPUnit tests',
                    'scope' => $this->getFinalScope($scope, $value),
                    'type' => $type,
                    'value' => $value,
                ],
            ],
            'events' => [
            ],
            'events_count' => 1,
            'labels' => null,
            'leakspeed' => '0',
            'message' => 'setup for PHPUnit tests',
            'scenario' => 'setup for PHPUnit tests',
            'scenario_hash' => '',
            'scenario_version' => '',
            'simulated' => false,
            'source' => [
                'scope' => $this->getFinalScope($scope, $value),
                'value' => $value,
            ],
            'start_at' => $startAt,
            'stop_at' => $stopAt,
        ];

        $result = $this->manageRequest(
            'POST',
            self::WATCHER_ALERT_ENDPOINT,
            [$body]
        );
    }
}
