<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use CrowdSec\Common\Client\RequestHandler\Curl;
use CrowdSec\Common\Client\RequestHandler\FileGetContents;
use CrowdSec\LapiClient\Bouncer as BouncerClient;
use CrowdSec\LapiClient\Constants as LapiConstants;
use CrowdSec\RemediationEngine\CacheStorage\AbstractCache;
use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\CacheStorage\Memcached;
use CrowdSec\RemediationEngine\CacheStorage\PhpFiles;
use CrowdSec\RemediationEngine\CacheStorage\Redis;
use CrowdSec\RemediationEngine\LapiRemediation;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use IPLib\Factory;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Cache\CacheException;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Processor;

/**
 * The class that apply a bounce.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
abstract class AbstractBouncer
{
    use Helper;

    /** @var array */
    protected $configs = [];
    /** @var LoggerInterface */
    protected $logger;
    /** @var LapiRemediation */
    protected $remediationEngine;

    public function __construct(
        array $configs,
        LapiRemediation $remediationEngine,
        ?LoggerInterface $logger = null
    ) {
        // @codeCoverageIgnoreStart
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        // @codeCoverageIgnoreEnd
        $this->logger = $logger;
        $this->remediationEngine = $remediationEngine;
        $this->configure($configs);
        $configs = $this->getConfigs();
        // Clean configs for lighter log
        unset($configs['text'], $configs['color']);
        $this->logger->debug('Instantiate bouncer', [
            'type' => 'BOUNCER_INIT',
            'logger' => \get_class($this->getLogger()),
            'remediation' => \get_class($this->getRemediationEngine()),
            'configs' => $configs,
        ]);
    }

    /**
     * Apply a bounce for current IP depending on remediation associated to this IP
     * (e.g. display a ban wall, captcha wall or do nothing in case of a bypass).
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function bounceCurrentIp(): void
    {
        // Retrieve the current IP (even if it is a proxy IP) or a testing IP
        $forcedTestIp = $this->getConfig('forced_test_ip');
        $ip = $forcedTestIp ?: $this->getRemoteIp();
        $ip = $this->handleForwardedFor($ip, $this->configs);
        $this->logger->info('Bouncing current IP', [
            'ip' => $ip,
        ]);
        $remediationData = $this->getRemediation($ip);
        $this->handleRemediation(
            $remediationData[Constants::REMEDIATION_KEY],
            $ip,
            $remediationData[Constants::ORIGIN_KEY]
        );
    }

    /**
     * This method clear the full data in cache.
     *
     * @return bool If the cache has been successfully cleared or not
     *
     * @throws BouncerException
     */
    public function clearCache(): bool
    {
        try {
            return $this->getRemediationEngine()->clearCache();
        } catch (\Throwable $e) {
            throw new BouncerException('Error while clearing cache: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Get the remediation for the specified IP using AppSec.
     *
     * @throws BouncerException
     * @throws CacheException
     */
    public function getAppSecRemediationForIp(string $ip): array
    {
        try {
            return $this->remediationEngine->getAppSecRemediation(
                $this->getAppSecHeaders($ip),
                $this->getRequestRawBody()
            );
        } catch (\Throwable $e) {
            throw new BouncerException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Retrieve Bouncer configuration by name.
     */
    public function getConfig(string $name)
    {
        return (isset($this->configs[$name])) ? $this->configs[$name] : null;
    }

    /**
     * Retrieve Bouncer configurations.
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * Get current http method.
     */
    abstract public function getHttpMethod(): string;

    /**
     * Get value of an HTTP request header. Ex: "X-Forwarded-For".
     */
    abstract public function getHttpRequestHeader(string $name): ?string;

    /**
     * Returns the logger instance.
     *
     * @return LoggerInterface the logger used by this library
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get the value of a posted field.
     */
    abstract public function getPostedVariable(string $name): ?string;

    public function getRemediationEngine(): LapiRemediation
    {
        return $this->remediationEngine;
    }

    /**
     * Get the remediation for the specified IP.
     *
     * @return array
     *               [
     *               'remediation': the remediation to apply (ex: 'ban', 'captcha', 'bypass')
     *               'origin': the origin of the remediation (ex: 'cscli', 'CAPI')
     *               ]
     *
     * @throws BouncerException
     * @throws CacheException
     */
    public function getRemediationForIp(string $ip): array
    {
        try {
            return $this->getRemediationEngine()->getIpRemediation($ip);
        } catch (\Throwable $e) {
            throw new BouncerException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Get the current IP, even if it's the IP of a proxy.
     */
    abstract public function getRemoteIp(): string;

    /**
     * Get current request headers.
     */
    abstract public function getRequestHeaders(): array;

    /**
     * Get current request host.
     */
    abstract public function getRequestHost(): string;

    /**
     * Get current request raw body.
     */
    abstract public function getRequestRawBody(): string;

    /**
     * Get current request uri.
     */
    abstract public function getRequestUri(): string;

    /**
     * Get current request user agent.
     */
    abstract public function getRequestUserAgent(): string;

    /**
     * Check if the bouncer is connected to a "Blocklist as a service" Lapi.
     */
    public function hasBaasUri(): bool
    {
        $url = $this->getRemediationEngine()->getClient()->getConfig('api_url');

        return 0 === strpos($url, Constants::BAAS_URL);
    }

    /**
     * This method prune the cache: it removes all the expired cache items.
     *
     * @return bool If the cache has been successfully pruned or not
     *
     * @throws BouncerException
     */
    public function pruneCache(): bool
    {
        try {
            return $this->getRemediationEngine()->pruneCache();
        } catch (\Throwable $e) {
            throw new BouncerException('Error while pruning cache: ' . $e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws BouncerException
     * @throws CacheException
     */
    public function pushUsageMetrics(
        string $bouncerName,
        string $bouncerVersion,
        string $bouncerType = LapiConstants::METRICS_TYPE
    ): array {
        try {
            return $this->remediationEngine->pushUsageMetrics($bouncerName, $bouncerVersion, $bouncerType);
        } catch (\Throwable $e) {
            throw new BouncerException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Used in stream mode only.
     * This method should be called periodically (ex: crontab) in an asynchronous way to update the bouncer cache.
     *
     * @return array Number of deleted and new decisions
     *
     * @throws BouncerException
     * @throws CacheException
     */
    public function refreshBlocklistCache(): array
    {
        try {
            return $this->getRemediationEngine()->refreshDecisions();
        } catch (\Throwable $e) {
            $message = 'Error while refreshing decisions: ' . $e->getMessage();
            throw new BouncerException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resetUsageMetrics(): void
    {
        // Retrieve metrics cache item
        $metricsItem = $this->getRemediationEngine()->getCacheStorage()->getItem(AbstractCache::ORIGINS_COUNT);
        if ($metricsItem->isHit()) {
            // Reset the metrics
            $metricsItem->set([]);
            $this->getRemediationEngine()->getCacheStorage()->getAdapter()->save($metricsItem);
        }
    }

    /**
     * Handle a bounce for current IP.
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws BouncerException
     */
    public function run(): bool
    {
        $result = false;
        try {
            if ($this->shouldBounceCurrentIp()) {
                $this->bounceCurrentIp();
                $result = true;
            }
        } catch (\Throwable $e) {
            $this->logger->error('Something went wrong during bouncing', [
                'type' => 'EXCEPTION_WHILE_BOUNCING',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if (true === $this->getConfig('display_errors')) {
                throw new BouncerException($e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        return $result;
    }

    /**
     * If the current IP should be bounced or not, matching custom business rules.
     * Must be called before trying to get remediation for the current IP, so that origins count is not already updated.
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    public function shouldBounceCurrentIp(): bool
    {
        $excludedURIs = $this->getConfig('excluded_uris') ?? [];
        $uri = $this->getRequestUri();
        if ($uri && \in_array($uri, $excludedURIs)) {
            return $this->handleBounceExclusion('This URI is excluded from bouncing: ' . $uri);
        }
        if (Constants::BOUNCING_LEVEL_DISABLED === $this->getRemediationEngine()->getConfig('bouncing_level')) {
            return $this->handleBounceExclusion('Bouncing is disabled by bouncing_level configuration');
        }

        return true;
    }

    /**
     * Process a simple cache test.
     *
     * @throws BouncerException
     * @throws CacheStorageException
     * @throws CacheException
     */
    public function testCacheConnection(): void
    {
        try {
            $cache = $this->getRemediationEngine()->getCacheStorage();
            $cache->getItem(AbstractCache::CONFIG);
        } catch (\Throwable $e) {
            $message = 'Error while testing cache connection: ' . $e->getMessage();
            throw new BouncerException($message, (int) $e->getCode(), $e);
        }
    }

    /**
     * Method based on superglobals to retrieve the raw body of the request.
     * If the body is too big (greater than the "appsec_max_body_size_kb" configuration),
     * it will be truncated to the maximum size + 1 kB.
     * In case of error, an empty string is returned.
     *
     * @param resource $stream The stream to read the body from
     *
     * @see https://www.php.net/manual/en/language.variables.superglobals.php
     */
    protected function buildRequestRawBody($stream): string
    {
        if (!is_resource($stream)) {
            $this->logger->error('Invalid stream resource', [
                'type' => 'BUILD_RAW_BODY',
            ]);

            return '';
        }
        $maxBodySize = $this->getRemediationEngine()->getConfig('appsec_max_body_size_kb') ??
                       Constants::APPSEC_DEFAULT_MAX_BODY_SIZE;

        try {
            return $this->buildRawBodyFromSuperglobals($maxBodySize, $stream, $_SERVER, $_POST, $_FILES);
        } catch (BouncerException $e) {
            $this->logger->error('Error while building raw body', [
                'type' => 'BUILD_RAW_BODY',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return '';
        }
    }

    /**
     * Returns a default "CrowdSec 403" HTML template.
     * The input $config should match the TemplateConfiguration input format.
     *
     * @return string The HTML compiled template
     */
    protected function getBanHtml(): string
    {
        $template = new Template('ban.html.twig');

        return $template->render($this->configs);
    }

    /**
     * Returns a default "CrowdSec Captcha (401)" HTML template.
     */
    protected function getCaptchaHtml(
        bool $error,
        string $captchaImageSrc,
        string $captchaResolutionFormUrl
    ): string {
        $template = new Template('captcha.html.twig');

        return $template->render(array_merge(
            $this->configs,
            [
                'error' => $error,
                'captcha_img' => $captchaImageSrc,
                'captcha_resolution_url' => $captchaResolutionFormUrl,
            ]
        ));
    }

    /**
     * @throws BouncerException
     * @throws CacheStorageException
     */
    protected function handleCache(array $configs, LoggerInterface $logger): AbstractCache
    {
        $cacheSystem = $configs['cache_system'] ?? Constants::CACHE_SYSTEM_PHPFS;
        switch ($cacheSystem) {
            case Constants::CACHE_SYSTEM_PHPFS:
                $cache = new PhpFiles($configs, $logger);
                break;
            case Constants::CACHE_SYSTEM_MEMCACHED:
                $cache = new Memcached($configs, $logger);
                break;
            case Constants::CACHE_SYSTEM_REDIS:
                $cache = new Redis($configs, $logger);
                break;
            default:
                throw new BouncerException("Unknown selected cache technology: $cacheSystem");
        }

        return $cache;
    }

    protected function handleClient(array $configs, LoggerInterface $logger): BouncerClient
    {
        $requestHandler = empty($configs['use_curl']) ? new FileGetContents($configs) : new Curl($configs);

        return new BouncerClient($configs, $requestHandler, $logger);
    }

    /**
     * Handle remediation for a given IP and origin.
     *
     * @throws CacheException
     * @throws InvalidArgumentException
     * @throws BouncerException
     */
    protected function handleRemediation(string $remediation, string $ip, string $origin): void
    {
        switch ($remediation) {
            case Constants::REMEDIATION_CAPTCHA:
                $this->handleCaptchaRemediation($ip, $origin);
                break;
            case Constants::REMEDIATION_BAN:
                $this->logger->debug('Will display a ban wall', [
                    'ip' => $ip,
                ]);
                // Increment ban origin count
                $this->getRemediationEngine()->updateMetricsOriginsCount(
                    $origin,
                    $remediation
                );
                $this->handleBanRemediation();
                break;
            case Constants::REMEDIATION_BYPASS:
            default:
                // Increment clean origin count
                $finalOrigin = AbstractCache::CLEAN_APPSEC === $origin ?
                    AbstractCache::CLEAN_APPSEC :
                    AbstractCache::CLEAN;
                $this->getRemediationEngine()->updateMetricsOriginsCount(
                    $finalOrigin,
                    Constants::REMEDIATION_BYPASS
                );
                break;
        }
    }

    /**
     * @codeCoverageIgnore
     */
    protected function redirectResponse(string $redirect): void
    {
        header("Location: $redirect");
        exit(0);
    }

    /**
     * Send HTTP response.
     *
     * @throws BouncerException
     *
     * @codeCoverageIgnore
     */
    protected function sendResponse(string $body, int $statusCode): void
    {
        switch ($statusCode) {
            case 401:
                header('HTTP/1.0 401 Unauthorized');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                break;
            case 403:
                header('HTTP/1.0 403 Forbidden');
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Cache-Control: post-check=0, pre-check=0', false);
                header('Pragma: no-cache');
                break;
            default:
                throw new BouncerException("Unhandled code $statusCode");
        }

        echo $body;

        exit(0);
    }

    /**
     * Build a captcha couple.
     *
     * @return array an array composed of two items, a "phrase" string representing the phrase and a "inlineImage"
     *               representing the image data
     */
    private function buildCaptchaCouple(): array
    {
        $captchaBuilder = new CaptchaBuilder();

        return [
            'phrase' => $captchaBuilder->getPhrase(),
            'inlineImage' => $captchaBuilder->build()->inline(),
        ];
    }

    /**
     * Check if the captcha filled by the user is correct or not.
     * We are permissive with the user:
     * - case is not sensitive
     * - (0 is interpreted as "o" and 1 in interpreted as "l").
     *
     * @param string $expected The expected phrase
     * @param string $try      The phrase to check (the user input)
     * @param string $ip       The IP of the use (for logging purpose)
     *
     * @return bool If the captcha input was correct or not
     *
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    private function checkCaptcha(string $expected, string $try, string $ip): bool
    {
        $solved = PhraseBuilder::comparePhrases($expected, $try);
        $this->logger->info('Captcha has been solved', [
            'type' => 'CAPTCHA_SOLVED',
            'ip' => $ip,
            'resolution' => $solved,
        ]);

        return $solved;
    }

    /**
     * Configure this instance.
     *
     * @param array $config An array with all configuration parameters
     */
    private function configure(array $config): void
    {
        // Process and validate input configuration.
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$configuration->cleanConfigs($config)]);
    }

    /**
     * @throws InvalidArgumentException
     * @throws BouncerException
     *
     * @codeCoverageIgnore
     */
    private function displayCaptchaWall(string $ip): void
    {
        $captchaVariables = $this->getCache()->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            ['resolution_failed', 'inline_image'],
            $ip
        );
        $body = $this->getCaptchaHtml(
            (bool) $captchaVariables['resolution_failed'],
            (string) $captchaVariables['inline_image'],
            ''
        );
        $this->sendResponse($body, 401);
    }

    private function getAppSecHeaders(string $ip): array
    {
        $requestHeaders = $this->getRequestHeaders();

        return array_merge(
            $requestHeaders,
            [
                Constants::HEADER_APPSEC_IP => $ip,
                Constants::HEADER_APPSEC_URI => $this->getRequestUri(),
                Constants::HEADER_APPSEC_HOST => $this->getRequestHost(),
                Constants::HEADER_APPSEC_VERB => $this->getHttpMethod(),
                Constants::HEADER_APPSEC_API_KEY => $this->remediationEngine->getClient()->getConfig('api_key'),
                Constants::HEADER_APPSEC_USER_AGENT => $this->getRequestUserAgent(),
            ]
        );
    }

    private function getCache(): AbstractCache
    {
        return $this->getRemediationEngine()->getCacheStorage();
    }

    /**
     * @throws BouncerException
     * @throws CacheException
     */
    private function getRemediation(string $ip): array
    {
        $remediationData = $this->getRemediationForIp($ip);
        $remediation = $remediationData[Constants::REMEDIATION_KEY];
        if ($this->shouldUseAppSec($remediation)) {
            $remediationData = $this->getAppSecRemediationForIp($ip);
        }

        return $remediationData;
    }

    /**
     * @return array [[string, string], ...] Returns IP ranges to trust as proxies as an array of comparables ip bounds
     */
    private function getTrustForwardedIpBoundsList(): array
    {
        return $this->getConfig('trust_ip_forward_array') ?? [];
    }

    /**
     * @throws BouncerException
     *
     * @codeCoverageIgnore
     */
    private function handleBanRemediation(): void
    {
        $body = $this->getBanHtml();
        $this->sendResponse($body, 403);
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function handleBounceExclusion(string $message): bool
    {
        $this->logger->debug('Will not bounce as exclusion criteria met', [
            'type' => 'SHOULD_NOT_BOUNCE',
            'message' => $message,
        ]);

        return false;
    }

    /**
     * @throws BouncerException
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function handleCaptchaRemediation(string $ip, string $origin): void
    {
        // Check captcha resolution form
        $this->handleCaptchaResolutionForm($ip);
        $cachedCaptchaVariables = $this->getCache()->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            ['has_to_be_resolved'],
            $ip
        );
        $mustResolve = false;
        if (null === $cachedCaptchaVariables['has_to_be_resolved']) {
            // Set up the first captcha remediation.
            $mustResolve = true;
            $this->logger->debug('First captcha resolution', [
                'ip' => $ip,
            ]);
            $this->initCaptchaResolution($ip);
        }

        // Display captcha page if this is required.
        if ($cachedCaptchaVariables['has_to_be_resolved'] || $mustResolve) {
            $this->logger->debug('Will display a captcha wall', [
                'ip' => $ip,
            ]);
            // Increment captcha origin count
            $this->getRemediationEngine()->updateMetricsOriginsCount(
                $origin,
                Constants::REMEDIATION_CAPTCHA
            );
            $this->displayCaptchaWall($ip);
        }
        // Increment clean origin count
        $finalOrigin = AbstractCache::CLEAN_APPSEC === $origin ? AbstractCache::CLEAN_APPSEC : AbstractCache::CLEAN;
        $this->getRemediationEngine()->updateMetricsOriginsCount(
            $finalOrigin,
            Constants::REMEDIATION_BYPASS
        );
        $this->logger->info('Captcha wall is not required (already solved)', [
            'ip' => $ip,
        ]);
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function handleCaptchaResolutionForm(string $ip): void
    {
        $cachedCaptchaVariables = $this->getCache()->getIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            [
                'has_to_be_resolved',
                'phrase_to_guess',
                'resolution_redirect',
            ],
            $ip
        );
        if ($this->shouldNotCheckResolution($cachedCaptchaVariables) || $this->refreshCaptcha($ip)) {
            return;
        }

        // Handle a captcha resolution try
        if (
            null !== $this->getPostedVariable('phrase')
            && null !== $cachedCaptchaVariables['phrase_to_guess']
        ) {
            $duration = $this->getConfig('captcha_cache_duration') ?? Constants::CACHE_EXPIRATION_FOR_CAPTCHA;
            if (
                $this->checkCaptcha(
                    (string) $cachedCaptchaVariables['phrase_to_guess'],
                    (string) $this->getPostedVariable('phrase'),
                    $ip
                )
            ) {
                // User has correctly filled the captcha
                $this->getCache()->setIpVariables(
                    Constants::CACHE_TAG_CAPTCHA,
                    ['has_to_be_resolved' => false],
                    $ip,
                    $duration,
                    [Constants::CACHE_TAG_CAPTCHA]
                );
                $unsetVariables = [
                    'phrase_to_guess',
                    'inline_image',
                    'resolution_failed',
                    'resolution_redirect',
                ];
                $this->getCache()->unsetIpVariables(
                    Constants::CACHE_TAG_CAPTCHA,
                    $unsetVariables,
                    $ip,
                    $duration,
                    [Constants::CACHE_TAG_CAPTCHA]
                );
                $redirect = $cachedCaptchaVariables['resolution_redirect'] ?? '/';
                $this->redirectResponse($redirect);
            } else {
                // The user failed to resolve the captcha.
                $this->getCache()->setIpVariables(
                    Constants::CACHE_TAG_CAPTCHA,
                    ['resolution_failed' => true],
                    $ip,
                    $duration,
                    [Constants::CACHE_TAG_CAPTCHA]
                );
            }
        }
    }

    /**
     * Handle X-Forwarded-For HTTP header to retrieve the IP to bounce.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    private function handleForwardedFor(string $ip, array $configs): string
    {
        $forwardedIp = null;
        if (empty($configs['forced_test_forwarded_ip'])) {
            $xForwardedForHeader = $this->getHttpRequestHeader('X-Forwarded-For');
            if (null !== $xForwardedForHeader) {
                $ipList = array_map('trim', array_values(array_filter(explode(',', $xForwardedForHeader))));
                $forwardedIp = end($ipList);
            }
        } elseif (Constants::X_FORWARDED_DISABLED === $configs['forced_test_forwarded_ip']) {
            $this->logger->debug('X-Forwarded-for usage is disabled', [
                'type' => 'DISABLED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
            ]);
        } else {
            $forwardedIp = (string) $configs['forced_test_forwarded_ip'];
            $this->logger->debug('X-Forwarded-for usage is forced', [
                'type' => 'FORCED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
                'x_forwarded_for_ip' => $forwardedIp,
            ]);
        }

        if (is_string($forwardedIp)) {
            if ($this->shouldTrustXforwardedFor($ip)) {
                $this->logger->debug('Detected IP is allowed for X-Forwarded-for usage', [
                    'type' => 'AUTHORIZED_X_FORWARDED_FOR_USAGE',
                    'original_ip' => $ip,
                    'x_forwarded_for_ip' => $forwardedIp,
                ]);

                return $forwardedIp;
            }
            $this->logger->warning('Detected IP is not allowed for X-Forwarded-for usage', [
                'type' => 'NON_AUTHORIZED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
                'x_forwarded_for_ip' => $forwardedIp,
            ]);
        }

        return $ip;
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function initCaptchaResolution(string $ip): void
    {
        $captchaCouple = $this->buildCaptchaCouple();
        $referer = $this->getHttpRequestHeader('REFERER');
        $captchaVariables = [
            'phrase_to_guess' => $captchaCouple['phrase'],
            'inline_image' => $captchaCouple['inlineImage'],
            'has_to_be_resolved' => true,
            'resolution_failed' => false,
            'resolution_redirect' => 'POST' === $this->getHttpMethod() && !empty($referer)
                ? $referer : '/',
        ];
        $duration = $this->getConfig('captcha_cache_duration') ?? Constants::CACHE_EXPIRATION_FOR_CAPTCHA;
        $this->getCache()->setIpVariables(
            Constants::CACHE_TAG_CAPTCHA,
            $captchaVariables,
            $ip,
            $duration,
            [Constants::CACHE_TAG_CAPTCHA]
        );
    }

    /**
     * @throws CacheException
     * @throws InvalidArgumentException
     */
    private function refreshCaptcha(string $ip): bool
    {
        if (null !== $this->getPostedVariable('refresh') && (int) $this->getPostedVariable('refresh')) {
            // Generate new captcha image for the user
            $captchaCouple = $this->buildCaptchaCouple();
            $captchaVariables = [
                'phrase_to_guess' => $captchaCouple['phrase'],
                'inline_image' => $captchaCouple['inlineImage'],
                'resolution_failed' => false,
            ];
            $duration = $this->getConfig('captcha_cache_duration') ?? Constants::CACHE_EXPIRATION_FOR_CAPTCHA;
            $this->getCache()->setIpVariables(
                Constants::CACHE_TAG_CAPTCHA,
                $captchaVariables,
                $ip,
                $duration,
                [Constants::CACHE_TAG_CAPTCHA]
            );

            return true;
        }

        return false;
    }

    /**
     * Check if captcha resolution is required or not.
     */
    private function shouldNotCheckResolution(array $captchaData): bool
    {
        $result = false;
        if (\in_array($captchaData['has_to_be_resolved'], [null, false])) {
            // Check not needed if 'has_to_be_resolved' cached flag has not been saved
            $result = true;
        } elseif ('POST' !== $this->getHttpMethod() || null === $this->getPostedVariable('crowdsec_captcha')) {
            // Check not needed if no form captcha form has been filled.
            $result = true;
        }

        return $result;
    }

    private function shouldTrustXforwardedFor(string $ip): bool
    {
        $parsedAddress = Factory::parseAddressString($ip, 3);
        if (null === $parsedAddress) {
            $this->logger->warning('IP is invalid', [
                'type' => 'INVALID_INPUT_IP',
                'ip' => $ip,
            ]);

            return false;
        }
        $comparableAddress = $parsedAddress->getComparableString();

        foreach ($this->getTrustForwardedIpBoundsList() as $comparableIpBounds) {
            if ($comparableAddress >= $comparableIpBounds[0] && $comparableAddress <= $comparableIpBounds[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if AppSec should be used for the current IP.
     *
     * If remediation is not bypass, it must always return false.
     */
    private function shouldUseAppSec(string $remediation): bool
    {
        $useAppSec = $this->getConfig('use_appsec');
        if (!$useAppSec || Constants::REMEDIATION_BYPASS !== $remediation) {
            return false;
        }

        $authType = $this->remediationEngine->getClient()->getConfig('auth_type');
        if (Constants::AUTH_TLS === $authType) {
            $this->logger->warning('Calling AppSec with a TLS-authenticated bouncer is not supported.', [
                'type' => 'APPSEC_LAPI_TLS_AUTH_UNSUPPORTED',
                'auth_type_config' => $authType,
                'use_appsec_config' => $useAppSec,
                'message' => 'Please use API key authentication for calling AppSec.',
            ]);

            return false;
        }

        return true;
    }
}
