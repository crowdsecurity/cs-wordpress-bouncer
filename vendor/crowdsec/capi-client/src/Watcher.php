<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\Client\AbstractClient;
use CrowdSec\CapiClient\Client\CapiHandler\CapiHandlerInterface;
use CrowdSec\CapiClient\Configuration\Watcher as WatcherConfig;
use CrowdSec\CapiClient\Storage\StorageInterface;
use CrowdSec\Common\Client\ClientException as CommonClientException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Uid\Uuid;

/**
 * The Watcher Client.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2022+ CrowdSec
 * @license   MIT License
 */
class Watcher extends AbstractClient
{
    /**
     * @var string The bouncer name for metrics
     */
    private const BOUNCER_NAME = 'php';
    /**
     * @var string The list of available digits
     */
    private const DIGITS = '0123456789';
    /**
     * @var string The list of available lowercase letters
     */
    private const LOWERS = 'abcdefghijklmnopqrstuvwxyz';
    /**
     * @var string The list of available uppercase letters
     */
    private const UPPERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /**
     * @var array
     */
    protected $configs;
    /**
     * @var array
     */
    private $headers;
    /**
     * @var string|null
     */
    private $machineId;
    /**
     * @var string|null
     */
    private $password;
    /**
     * @var StorageInterface
     */
    private $storage;
    /**
     * @var string|null
     */
    private $token;

    public function __construct(
        array $configs,
        StorageInterface $storage,
        ?CapiHandlerInterface $capiHandler = null,
        ?LoggerInterface $logger = null
    ) {
        $this->configure($configs);
        $this->headers = ['User-Agent' => $this->formatUserAgent($this->configs)];
        $this->storage = $storage;
        $this->configs['api_url'] =
            Constants::ENV_PROD === $this->getConfig('env') ? Constants::URL_PROD : Constants::URL_DEV;
        parent::__construct($this->configs, $capiHandler, $logger);
    }

    /**
     * Helper to create well formatted signal array.
     *
     * @param array   $properties
     *                            Array containing signal properties
     *                            $properties = [
     *                            'scenario' => (string) Scenario name : <yourProductShortName>/<ScenarioName>
     *                            'created_at' => (DateTimeInterface) Date of the alert creation
     *                            'message' => (string) Details of the alert,
     *                            'start_at' => (DateTimeInterface) First event date for alert
     *                            'stop_at' => (DateTimeInterface) Last event date for alert
     *                            ];
     * @param array   $source
     *                            Array containing source data
     *                            $source = [
     *                            'scope' => (string) ip, range, country or any known scope
     *                            'value' => (string) depends on the scope (could be an ip, a range, etc.)
     *                            ];
     * @param array[] $decisions
     *                            Array of decisions. Each decision is an array too.
     *                            $decisions = [
     *                            [
     *                            'id' => (int) The decision id if known, 0 otherwise
     *                            'duration' => (int) Time to live of the decision in seconds
     *                            'scenario' => (string) Scenario name : <yourProductShortName>/<ScenarioName>
     *                            'origin' => (string) Origin of the decision (default to "crowdsec")
     *                            'scope' => (string) ip, range, country or any known scope
     *                            'value' => (string) depends on the scope (could be an ip, a range, etc.)
     *                            'type' => (string) Decision type: ban, captcha or any custom remediation
     *                            ],
     *                            ...
     *                            ]
     *
     * @throws ClientException
     */
    public function buildSignal(array $properties, array $source, array $decisions = [[]]): array
    {
        $createdAt = $this->formatDate($this->validateDateInput($properties['created_at'] ?? null));
        $startAt = isset($properties['start_at']) ?
            $this->formatDate($this->validateDateInput($properties['start_at'])) :
            $createdAt;
        $stopAt = isset($properties['stop_at']) ?
            $this->formatDate($this->validateDateInput($properties['stop_at'])) :
            $createdAt;
        $machineId = $this->storage->retrieveMachineId();
        if (!$machineId) {
            $this->ensureRegister();
            $machineId = $this->storage->retrieveMachineId();
        }
        $scenario = $properties['scenario'] ?? '';
        $scenarioTrust = $properties['scenario_trust'] ?? Constants::TRUST_MANUAL;
        $scenarioHash = $properties['scenario_hash'] ?? '';
        $scenarioVersion = $properties['scenario_version'] ?? '';
        $message = $properties['message'] ?? '';
        $uuid = $properties['uuid'] ?? Uuid::v4()->toRfc4122();
        $context = $properties['context'] ?? [];

        $properties = [
            'scenario' => $scenario,
            'scenario_hash' => $scenarioHash,
            'scenario_version' => $scenarioVersion,
            'scenario_trust' => $scenarioTrust,
            'created_at' => $createdAt,
            'machine_id' => $machineId,
            'message' => $message,
            'start_at' => $startAt,
            'stop_at' => $stopAt,
            'uuid' => $uuid,
            'context' => $context,
        ];

        $sourceScope = $source['scope'] ?? Constants::SCOPE_IP;
        $sourceValue = $source['value'] ?? '';

        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];

        $decisions = $this->formatDecisions($decisions, $scenario, $sourceScope, $sourceValue, $uuid);

        try {
            $signal = new Signal($properties, $source, $decisions);
        } catch (\Exception $e) {
            throw new ClientException('Something went wrong while creating signal: ' . $e->getMessage());
        }

        return $signal->toArray();
    }

    /**
     * Helper to build a generic "ban" signal for some IP
     * To create a more advanced signal structure, use the buildSignal method instead.
     *
     * @throws ClientException
     */
    public function buildSimpleSignalForIp(
        string $ip,
        string $scenario,
        ?\DateTimeInterface $createdAt,
        string $message = '',
        int $duration = Constants::DURATION
    ): array {
        return $this->buildSignal(
            [
                'scenario' => $scenario,
                'created_at' => $createdAt,
                'message' => $message,
            ],
            [
                'value' => $ip,
            ],
            [
                [
                    'duration' => $duration,
                ],
            ]
        );
    }

    /**
     * Process an enroll call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_enroll
     *
     * @throws ClientException
     */
    public function enroll(string $name, bool $overwrite, string $enrollKey, array $tags = []): array
    {
        $tags = $this->normalizeTags($tags);

        $params = [
            'name' => $name,
            'overwrite' => $overwrite,
            'attachment_key' => $enrollKey,
            'tags' => $tags,
        ];

        return $this->manageRequest('POST', Constants::ENROLL_ENDPOINT, $params);
    }

    /**
     * Process a decisions stream call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/get_decisions_stream
     *
     * @throws ClientException
     */
    public function getStreamDecisions(): array
    {
        return $this->manageRequest('GET', Constants::DECISIONS_STREAM_ENDPOINT);
    }

    /**
     * Process a signals call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_signals
     *
     * @throws ClientException
     */
    public function pushSignals(array $signals): array
    {
        return $this->manageRequest('POST', Constants::SIGNALS_ENDPOINT, $signals);
    }

    /**
     * Check if two indexed arrays are equals.
     */
    private function areEquals(array $arrayOne, array $arrayTwo): bool
    {
        $countOne = count($arrayOne);

        return $countOne === count($arrayTwo) && $countOne === count(array_intersect($arrayOne, $arrayTwo));
    }

    /**
     * Create a simple metrics array.
     *
     * @return array[]
     *
     * @throws ClientException
     */
    private function buildSimpleMetrics(): array
    {
        $userAgentPart = explode('/', $this->headers['User-Agent'], 2);
        $metrics = $this->getConfig('metrics');

        return [
            'bouncers' => [
                [
                    'last_pull' => $metrics['bouncer']['last_pull'] ?? $this->formatDate(null),
                    'custom_name' => $metrics['bouncer']['custom_name'] ?? $userAgentPart[0],
                    'name' => self::BOUNCER_NAME,
                    'version' => $metrics['bouncer']['version'] ?? $userAgentPart[1],
                ],
            ],
            'machines' => [
                [
                    'last_update' => $metrics['machine']['last_update'] ?? $this->formatDate(null),
                    'name' => $metrics['machine']['name'] ?? $userAgentPart[0],
                    'last_push' => $metrics['machine']['last_push'] ?? $this->formatDate(null),
                    'version' => $metrics['machine']['version'] ?? $userAgentPart[1],
                ],
            ],
        ];
    }

    /**
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new WatcherConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
    }

    /**
     * Convert seconds into duration format : XhYmZs (example 86400 => 24h0m0s).
     */
    private function convertSecondsToDuration(int $seconds): string
    {
        return sprintf('%dh%dm%ds', intval($seconds / 3600), intval($seconds / 60) % 60, $seconds % 60);
    }

    /**
     * Ensure that machine is registered and that we have a token.
     *
     * @throws ClientException
     */
    private function ensureAuth(): void
    {
        $this->ensureRegister();
        $this->token = $this->storage->retrieveToken();
        if ($this->shouldLogin()) {
            $this->handleLogin();
        }
    }

    /**
     * Ensure that machine credentials are ready tu use.
     *
     * @throws ClientException
     */
    private function ensureRegister(): void
    {
        $this->machineId = $this->storage->retrieveMachineId();
        $this->password = $this->storage->retrievePassword();
        if ($this->shouldRefreshCredentials($this->machineId, $this->password, $this->configs)) {
            $this->refreshCredentials();
            $this->register();
        }
    }

    /**
     * @throws ClientException
     */
    private function formatDate(?\DateTimeInterface $date): string
    {
        try {
            $date = $date ?: new \DateTime('now', new \DateTimeZone('UTC'));

            return $date->format(Constants::DATE_FORMAT);
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new ClientException('Something went wrong while formatting date');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @throws ClientException
     */
    private function formatDecisions(
        array $decisions,
        string $scenario,
        string $scope,
        string $value,
        string $uuid
    ): array {
        $result = [];
        foreach ($decisions as $decision) {
            if (!\is_array($decision)) {
                $message = 'Decision must be an array';
                $this->logger->error($message, [
                    'type' => 'WATCHER_CLIENT_FORMAT_DECISIONS',
                ]);
                throw new ClientException($message);
            }
            $duration = $decision['duration'] ?? Constants::DURATION;
            if (!\is_int($duration)) {
                $message = 'Decision duration must be an integer';
                $this->logger->error($message, [
                    'type' => 'WATCHER_CLIENT_FORMAT_DECISIONS',
                ]);
                throw new ClientException($message);
            }

            $result[] = [
                'id' => $decision['id'] ?? 0,
                'uuid' => $decision['uuid'] ?? $uuid,
                'duration' => $this->convertSecondsToDuration($duration),
                'scenario' => $decision['scenario'] ?? $scenario,
                'origin' => $decision['origin'] ?? Constants::ORIGIN,
                'scope' => $decision['scope'] ?? $scope,
                'value' => $decision['value'] ?? $value,
                'type' => $decision['type'] ?? Constants::REMEDIATION_BAN,
                'simulated' => $decision['simulated'] ?? false,
            ];
        }

        return $result;
    }

    /**
     * Format User-Agent header. <PHP CAPI client prefix>_<custom suffix>/<vX.Y.Z>.
     */
    private function formatUserAgent(array $configs = []): string
    {
        $userAgentSuffix = !empty($configs['user_agent_suffix']) ? '_' . $configs['user_agent_suffix'] : '';
        $userAgentVersion =
            !empty($configs['user_agent_version']) ? $configs['user_agent_version'] : Constants::VERSION;

        return Constants::USER_AGENT_PREFIX . $userAgentSuffix . '/' . $userAgentVersion;
    }

    /**
     * Generate a random machine_id.
     *
     * @throws ClientException|\Exception
     */
    private function generateMachineId(array $configs = []): string
    {
        $prefix = !empty($configs['machine_id_prefix']) ? $configs['machine_id_prefix'] : '';

        return $prefix . $this->generateRandomString(
            Constants::MACHINE_ID_LENGTH - strlen($prefix),
            self::LOWERS . self::DIGITS
        );
    }

    /**
     * Generate a random password.
     *
     * @throws ClientException|\Exception
     */
    private function generatePassword(): string
    {
        return $this->generateRandomString(Constants::PASSWORD_LENGTH, self::UPPERS . self::LOWERS . self::DIGITS);
    }

    /**
     * Generate a cryptographically secure random string.
     *
     * @throws ClientException
     * @throws \Exception
     */
    private function generateRandomString(int $length, string $chars): string
    {
        $res = '';
        if ($length < 1) {
            return $res;
        }
        $chLen = strlen($chars);
        if ($chLen < 1) {
            throw new ClientException('There must be at least one allowed character.');
        }
        for ($i = 0; $i < $length; ++$i) {
            $res .= $chars[random_int(0, $chLen - 1)];
        }

        return $res;
    }

    /**
     * Retrieve a fresh token from login.
     *
     * @throws ClientException
     */
    private function handleLogin(): void
    {
        $loginResponse = $this->login();

        $this->token = $loginResponse['token'] ?? null;
        if (!$this->token) {
            $message = 'Login response does not contain required token.';
            $this->logger->error($message, [
                'type' => 'WATCHER_CLIENT_HANDLE_LOGIN',
                'response' => $loginResponse,
            ]);
            throw new ClientException($message, 401);
        }
        $this->storage->storeToken($this->token);
        $configScenarios = $this->getConfig('scenarios');
        $this->storage->storeScenarios($configScenarios ?: []);
        try {
            $this->pushMetrics();
        } catch (\Exception $e) {
            $this->logger->info(
                'Push metrics failed. Will try again on next login.',
                [
                    'type' => 'WATCHER_CLIENT_PUSH_METRICS_FAILED',
                    'error' => $e->getMessage(),
                ]
            );
        }
    }

    /**
     * Handle required token (JWT) in header for next CAPI calls.
     *
     * @throws ClientException
     */
    private function handleTokenHeader(): array
    {
        if (!$this->token) {
            $message = 'Token is required.';
            $this->logger->error($message, ['type' => 'WATCHER_CLIENT_HANDLE_TOKEN']);
            throw new ClientException('Token is required.', 401);
        }

        return ['Authorization' => sprintf('Bearer %s', $this->token)];
    }

    /**
     * Process a login call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers_login
     *
     * @throws ClientException
     */
    private function login(): array
    {
        try {
            return $this->request(
                'POST',
                Constants::LOGIN_ENDPOINT,
                [
                    'password' => $this->password,
                    'machine_id' => $this->machineId,
                    'scenarios' => $this->getConfig('scenarios'), ],
                $this->headers
            );
        } catch (CommonClientException $e) {
            throw new ClientException('Error during login: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Make a request and manage retry attempts (login and register errors).
     *
     * @throws ClientException
     */
    private function manageRequest(
        string $method,
        string $endpoint,
        array $parameters = []
    ): array {
        $this->logger->debug('Now processing a watcher request', [
            'type' => 'WATCHER_REQUEST',
            'method' => $method,
            'endpoint' => $endpoint,
            'parameters' => $parameters,
        ]);
        $this->ensureAuth();
        $loginRetry = 0;
        $lastMessage = '';
        $response = [];
        $retry = false;
        do {
            try {
                if ($retry) {
                    $retry = false;
                    $this->handleLogin();
                }
                $headers = array_merge($this->headers, $this->handleTokenHeader());
                $response = $this->request($method, $endpoint, $parameters, $headers);
            } catch (CommonClientException $e) {
                $message = $e->getMessage();
                $code = $e->getCode();
                /**
                 * If there is an issue with credentials or token, CAPI returns a 401 error.
                 * In this case only, we try to log in again.
                 */
                if (401 !== $code) {
                    $this->logger->error($message, [
                        'type' => 'WATCHER_REQUEST_ERROR',
                        'code' => $code,
                    ]);
                    throw new ClientException('Error during request: ' . $message, $code, $e);
                }
                $this->logger->info($message, [
                    'type' => 'WATCHER_REQUEST_LOGIN_RETRY',
                    'code' => $code,
                ]);
                ++$loginRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($loginRetry <= Constants::LOGIN_RETRY));
        if ($loginRetry > Constants::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: $lastMessage";
            $this->logger->error($message, [
                'type' => 'WATCHER_REQUEST_TOO_MANY_ATTEMPTS',
            ]);
            throw new ClientException($message);
        }

        return $response;
    }

    /**
     * Validate tags and and returns an indexed array unique.
     *
     * @throws ClientException
     */
    private function normalizeTags(array $tags): array
    {
        foreach ($tags as $tag) {
            if (!is_string($tag)) {
                $message = 'Tag must be a string: ' . gettype($tag) . ' given.';
                $this->logger->error($message, ['type' => 'WATCHER_NORMALIZE_TAGS']);
                throw new ClientException($message, 500);
            }
            if (empty($tag)) {
                $message = 'Tag must not be empty';
                $this->logger->error($message, ['type' => 'WATCHER_NORMALIZE_TAGS']);
                throw new ClientException($message, 500);
            }
        }

        return array_values(array_unique($tags));
    }

    /**
     * Push metrics about enrolled machines and bouncers.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_metrics
     *
     * @throws ClientException
     * @throws CommonClientException
     */
    private function pushMetrics(): void
    {
        $metrics = $this->buildSimpleMetrics();
        $headers = array_merge($this->headers, $this->handleTokenHeader());
        $result = $this->request('POST', Constants::METRICS_ENDPOINT, $metrics, $headers);

        $this->logger->debug(
            'Push metrics result.',
            [
                'type' => 'WATCHER_CLIENT_PUSH_METRICS_RESULT',
                'result' => $result,
            ]
        );
    }

    /**
     * Generate and store new machine_id/password pair.
     *
     * @throws ClientException|\Exception
     */
    private function refreshCredentials(): void
    {
        $this->machineId = $this->generateMachineId($this->configs);
        $this->password = $this->generatePassword();
        $this->storage->storeMachineId($this->machineId);
        $this->storage->storePassword($this->password);
    }

    /**
     * Process a register call to CAPI.
     *
     * @see https://crowdsecurity.github.io/api_doc/index.html?urls.primaryName=CAPI#/watchers/post_watchers
     *
     * @throws ClientException|\Exception
     */
    private function register(): void
    {
        $registerRetry = 0;
        $lastMessage = '';
        $retry = false;
        do {
            try {
                if ($retry) {
                    $retry = false;
                    $this->refreshCredentials();
                }
                $this->request(
                    'POST',
                    Constants::REGISTER_ENDPOINT,
                    [
                        'password' => $this->password,
                        'machine_id' => $this->machineId, ],
                    $this->headers
                );
            } catch (CommonClientException $e) {
                $message = $e->getMessage();
                $code = $e->getCode();
                /**
                 * If the machine_id is already registered, CAPI returns a 500 error.
                 * In this case only, we try to register again with new credentials.
                 */
                if (500 !== $code) {
                    $this->logger->error($message, [
                        'type' => 'WATCHER_REGISTER_ERROR',
                        'code' => $code,
                    ]);
                    throw new ClientException('Error during register: ' . $message, $code, $e);
                }
                $this->logger->info($message, [
                    'type' => 'WATCHER_REGISTER_RETRY',
                    'code' => $code,
                ]);
                ++$registerRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($registerRetry <= Constants::REGISTER_RETRY));
        if ($registerRetry > Constants::REGISTER_RETRY) {
            $message = "Could not register after $registerRetry attempts. Last error was: $lastMessage";
            $this->logger->error($message, ['type' => 'WATCHER_REGISTER_TOO_MANY_ATTEMPTS']);
            throw new ClientException($message);
        }
    }

    /**
     * Check if we should log in (handle token and scenarios).
     */
    private function shouldLogin(): bool
    {
        if (!$this->token) {
            return true;
        }

        // Verify that we have stored scenarios and that the match with current scenarios
        $storedScenarios = $this->storage->retrieveScenarios();
        if (!$storedScenarios) {
            return true;
        }
        $configScenarios = $this->getConfig('scenarios');

        return !$this->areEquals($storedScenarios, $configScenarios ?: []);
    }

    /**
     * Check if we should refresh machine_id/password pair.
     */
    private function shouldRefreshCredentials(?string $machineId, ?string $password, array $configs): bool
    {
        if (!$machineId || !$password) {
            return true;
        }
        $prefix = !empty($configs['machine_id_prefix']) ? $configs['machine_id_prefix'] : null;
        // Verify that current machine_id starts with configured prefix
        if ($prefix) {
            return 0 !== substr_compare($machineId, $prefix, 0, strlen($prefix));
        }

        return false;
    }

    /**
     * @throws ClientException
     */
    private function validateDateInput($input): ?\DateTimeInterface
    {
        if (!\is_null($input) && !($input instanceof \DateTimeInterface)) {
            $message = 'Date input must be null or implement DateTimeInterface';
            $this->logger->error($message, [
                'type' => 'WATCHER_CLIENT_VALIDATE_DATE',
            ]);

            throw new ClientException($message);
        }

        return $input;
    }
}
