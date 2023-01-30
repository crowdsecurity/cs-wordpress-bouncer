<?php

declare(strict_types=1);

namespace CrowdSec\CapiClient;

use CrowdSec\CapiClient\Configuration\Watcher as WatcherConfig;
use CrowdSec\CapiClient\RequestHandler\RequestHandlerInterface;
use CrowdSec\CapiClient\Storage\StorageInterface;
use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

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
     * @var string The decisions stream endpoint
     */
    public const DECISIONS_STREAM_ENDPOINT = '/decisions/stream';
    /**
     * @var string The list of available digits
     */
    private const DIGITS = '0123456789';
    /**
     * @var string The watchers enroll endpoint
     */
    public const ENROLL_ENDPOINT = '/watchers/enroll';
    /**
     * @var string The watchers login endpoint
     */
    public const LOGIN_ENDPOINT = '/watchers/login';
    /**
     * @var int The number of login retry attempts in case of 401
     */
    public const LOGIN_RETRY = 1;
    /**
     * @var string The list of available lowercase letters
     */
    private const LOWERS = 'abcdefghijklmnopqrstuvwxyz';
    /**
     * @var int The machine_id length
     */
    public const MACHINE_ID_LENGTH = 48;
    /**
     * @var int The password length
     */
    public const PASSWORD_LENGTH = 32;
    /**
     * @var string The watchers register endpoint
     */
    public const REGISTER_ENDPOINT = '/watchers';
    /**
     * @var int The number of register retry attempts in case of 500
     */
    public const REGISTER_RETRY = 1;
    /**
     * @var string The signals push endpoint
     */
    public const SIGNALS_ENDPOINT = '/signals';
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
        RequestHandlerInterface $requestHandler = null,
        LoggerInterface $logger = null
    ) {
        $this->configure($configs);
        $this->headers = ['User-Agent' => $this->formatUserAgent($this->configs)];
        $this->storage = $storage;
        $this->configs['api_url'] =
            Constants::ENV_PROD === $this->getConfig('env') ? Constants::URL_PROD : Constants::URL_DEV;
        parent::__construct($this->configs, $requestHandler, $logger);
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

        return $this->manageRequest('POST', self::ENROLL_ENDPOINT, $params);
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
        return $this->manageRequest('GET', self::DECISIONS_STREAM_ENDPOINT);
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
        return $this->manageRequest('POST', self::SIGNALS_ENDPOINT, $signals);
    }

    /**
     * Convert seconds into duration format : XhYmZs (example 86400 => 24h0m0s).
     */
    private function convertSecondsToDuration(int $seconds): string
    {
        return sprintf('%dh%dm%ds', intval($seconds / 3600), intval($seconds / 60) % 60, $seconds % 60);
    }

    /**
     * Helper to create well formatted signal array.
     *
     * @throws ClientException
     */
    public function createSignal(
        string $scenario,
        string $sourceValue,
        ?\DateTimeInterface $startAt,
        ?\DateTimeInterface $stopAt,
        string $message = '',
        string $sourceScope = Constants::SCOPE_IP,
        int $decisionDuration = Constants::DURATION,
        string $decisionType = Constants::REMEDIATION_BAN
    ): array {
        try {
            $currentTime = new DateTime('now', new DateTimeZone('UTC'));
            $createdAt = $currentTime->format(Constants::DATE_FORMAT);
            $startAt = $startAt ? $startAt->format(Constants::DATE_FORMAT) : $createdAt;
            $stopAt = $stopAt ? $stopAt->format(Constants::DATE_FORMAT) : $createdAt;
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            throw new ClientException('Something went wrong with date during signal creation');
            // @codeCoverageIgnoreEnd
        }

        $machineId = $this->storage->retrieveMachineId();
        if (!$machineId) {
            $this->ensureRegister();
            $machineId = $this->storage->retrieveMachineId();
        }

        $properties = [
            'scenario' => $scenario,
            'scenario_hash' => '',
            'scenario_version' => '',
            'created_at' => $createdAt,
            'machine_id' => $machineId,
            'message' => $message,
            'start_at' => $startAt,
            'stop_at' => $stopAt,
        ];
        $source = [
            'scope' => $sourceScope,
            'value' => $sourceValue,
        ];
        $decisions = [
            [
                'id' => 0,
                'duration' => $this->convertSecondsToDuration($decisionDuration),
                'scenario' => $scenario,
                'origin' => Constants::ORIGIN,
                'scope' => $sourceScope,
                'value' => $sourceValue,
                'type' => $decisionType,
            ],
        ];

        $signal = new Signal($properties, $source, $decisions);

        return $signal->toArray();
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
     * Process and validate input configurations.
     */
    private function configure(array $configs): void
    {
        $configuration = new WatcherConfig();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($configs)]);
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
     * @throws ClientException
     */
    private function generateMachineId(array $configs = []): string
    {
        $prefix = !empty($configs['machine_id_prefix']) ? $configs['machine_id_prefix'] : '';

        return $prefix . $this->generateRandomString(
            self::MACHINE_ID_LENGTH - strlen($prefix),
            self::LOWERS . self::DIGITS
        );
    }

    /**
     * Generate a random password.
     *
     * @throws ClientException
     */
    private function generatePassword(): string
    {
        return $this->generateRandomString(self::PASSWORD_LENGTH, self::UPPERS . self::LOWERS . self::DIGITS);
    }

    /**
     * Generate a cryptographically secure random string.
     *
     * @throws ClientException
     * @throws \Exception
     */
    private function generateRandomString(int $length, string $chars): string
    {
        if ($length < 1) {
            throw new ClientException('Length must be greater than zero.');
        }
        $chLen = strlen($chars);
        if ($chLen < 1) {
            throw new ClientException('There must be at least one allowed character.');
        }
        $res = '';
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
                'response' => $loginResponse
            ]);
            throw new ClientException($message, 401);
        }
        $this->storage->storeToken($this->token);
        $configScenarios = $this->getConfig('scenarios');
        $this->storage->storeScenarios($configScenarios ?: []);
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
        return $this->request(
            'POST',
            self::LOGIN_ENDPOINT,
            [
                'password' => $this->password,
                'machine_id' => $this->machineId,
                'scenarios' => $this->getConfig('scenarios'), ],
            $this->headers
        );
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
            } catch (ClientException $e) {
                $message = $e->getMessage();
                $code = $e->getCode();
                /**
                 * If there is an issue with credentials or token, CAPI returns a 401 error.
                 * In this case only, we try to log in again.
                 */
                if (401 !== $code) {
                    $this->logger->error($message, [
                        'type' => 'WATCHER_REQUEST_ERROR',
                        'code' => $code
                    ]);
                    throw new ClientException($message, $code);
                }
                $this->logger->info($message, [
                    'type' => 'WATCHER_REQUEST_LOGIN_RETRY',
                    'code' => $code
                ]);
                ++$loginRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($loginRetry <= self::LOGIN_RETRY));
        if ($loginRetry > self::LOGIN_RETRY) {
            $message = "Could not login after $loginRetry attempts. Last error was: $lastMessage";
            $this->logger->error($message, [
                'type' => 'WATCHER_REQUEST_TOO_MANY_ATTEMPTS'
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
     * Generate and store new machine_id/password pair.
     *
     * @throws ClientException
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
     * @throws ClientException
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
                    self::REGISTER_ENDPOINT,
                    [
                        'password' => $this->password,
                        'machine_id' => $this->machineId, ],
                    $this->headers
                );
            } catch (ClientException $e) {
                $message = $e->getMessage();
                $code = $e->getCode();
                /**
                 * If the machine_id is already registered, CAPI returns a 500 error.
                 * In this case only, we try to register again with new credentials.
                 */
                if (500 !== $code) {
                    $this->logger->error($message, [
                        'type' => 'WATCHER_REGISTER_ERROR',
                        'code' => $code
                    ]);
                    throw new ClientException($message, $code);
                }
                $this->logger->info($message, [
                    'type' => 'WATCHER_REGISTER_RETRY',
                    'code' => $code
                ]);
                ++$registerRetry;
                $retry = true;
                $lastMessage = $e->getMessage();
            }
        } while ($retry && ($registerRetry <= self::REGISTER_RETRY));
        if ($registerRetry > self::REGISTER_RETRY) {
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
}
