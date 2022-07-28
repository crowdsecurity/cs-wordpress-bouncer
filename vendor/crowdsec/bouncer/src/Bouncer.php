<?php

namespace CrowdSecBouncer;

require_once __DIR__ . '/templates/captcha.php';
require_once __DIR__ . '/templates/access-forbidden.php';

use CrowdSecBouncer\Fixes\Gregwar\Captcha\CaptchaBuilder;
use CrowdSecBouncer\RestClient\ClientAbstract;
use ErrorException;
use Gregwar\Captcha\PhraseBuilder;
use IPLib\Factory;
use IPLib\ParseStringFlag;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Component\Cache\Exception\CacheException;
use Symfony\Component\Config\Definition\Processor;

/**
 * The main Class of this package. This is the first entry point of any PHP Bouncers using this library.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2020+ CrowdSec
 * @license   MIT License
 */
class Bouncer
{
    /** @var LoggerInterface */
    private $logger;

    /** @var ApiCache */
    private $apiCache;

    /** @var int */
    private $maxRemediationLevelIndex;

    /** @var array */
    private $configs = [];

    /**
     * @param array $configs
     * @param LoggerInterface|null $logger
     * @throws BouncerException
     * @throws CacheException
     * @throws ErrorException|InvalidArgumentException
     */
    public function __construct(array $configs, LoggerInterface $logger = null)
    {
        if (!$logger) {
            $logger = new Logger('null');
            $logger->pushHandler(new NullHandler());
        }
        $this->logger = $logger;
        $this->configure($configs);
        /** @var int */
        $index = array_search(
            $this->configs['max_remediation_level'],
            Constants::ORDERED_REMEDIATIONS
        );
        $this->maxRemediationLevelIndex = $index;

        $this->apiCache = new ApiCache(
            $this->configs,
            $logger
        );

        $this->logger->debug('', [
            'type' => 'BOUNCER_INIT',
            'logger' => \get_class($this->logger),
            'max_remediation_level' => $this->maxRemediationLevelIndex,
            'configs' => $this->configs
        ]);
    }


    /**
     * Retrieve Bouncer configurations
     *
     * @return array
     */
    public function getConfigs(): array
    {
        return $this->configs;
    }

    /**
     * Retrieve Bouncer configuration by name
     *
     */
    public function getConfig($name)
    {
        return $this->configs[$name];
    }

    /**
     * Configure this instance.
     *
     * @param array $config An array with all configuration parameters
     *
     */
    private function configure(array $config): void
    {
        // Process and validate input configuration.
        $configuration = new Configuration();
        $processor = new Processor();
        $this->configs = $processor->processConfiguration($configuration, [$config]);
    }

    /**
     * Cap the remediation to a fixed value given in configuration.
     *
     * @param string $remediation The maximum remediation that can ban applied (ex: 'ban', 'captcha', 'bypass')
     *
     * @return string $remediation The resulting remediation to use (ex: 'ban', 'captcha', 'bypass')
     */
    private function capRemediationLevel(string $remediation): string
    {
        $currentIndex = array_search($remediation, Constants::ORDERED_REMEDIATIONS);
        if ($currentIndex < $this->maxRemediationLevelIndex) {
            return Constants::ORDERED_REMEDIATIONS[$this->maxRemediationLevelIndex];
        }

        return $remediation;
    }

    /**
     * Get the remediation for the specified IP. This method use the cache layer.
     * In live mode, when no remediation was found in cache,
     * the cache system will call the API to check if there is a decision.
     *
     * @param string $ip The IP to check
     *
     * @return string the remediation to apply (ex: 'ban', 'captcha', 'bypass')
     *
     * @throws InvalidArgumentException|\Psr\Cache\CacheException|BouncerException
     */
    public function getRemediationForIp(string $ip): string
    {
        $address = Factory::parseAddressString($ip, ParseStringFlag::MAY_INCLUDE_ZONEID);
        if (null === $address) {
            throw new BouncerException("IP $ip format is invalid.");
        }
        $remediation = $this->apiCache->get($address);

        return $this->capRemediationLevel($remediation);
    }

    /**
     * Returns a default "CrowdSec 403" HTML template to display to a web browser using a banned IP.
     * The input $config should match the TemplateConfiguration input format.
     *
     * @param array $config An array of template configuration parameters
     *
     * @return string The HTML compiled template
     */
    public static function getAccessForbiddenHtmlTemplate(array $config): string
    {
        // Process template configuration.
        $configuration = new TemplateConfiguration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [$config]);

        ob_start();
        displayAccessForbiddenTemplate($config);

        return ob_get_clean();
    }

    /**
     * Returns a default "CrowdSec Captcha" HTML template to display to a web browser using a captchable IP.
     * The input $config should match the TemplateConfiguration input format.
     */
    public static function getCaptchaHtmlTemplate(
        bool $error,
        string $captchaImageSrc,
        string $captchaResolutionFormUrl,
        array $config
    ): string {
        // Process template configuration.
        $configuration = new TemplateConfiguration();
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, [$config]);

        ob_start();
        displayCaptchaTemplate($error, $captchaImageSrc, $captchaResolutionFormUrl, $config);

        return ob_get_clean();
    }

    /**
     * Used in stream mode only.
     * This method should be called only to force a cache warm up.
     *
     * @return array "count": number of decisions added, "errors": decisions not added
     *
     * @throws InvalidArgumentException|\Psr\Cache\CacheException|BouncerException
     */
    public function warmBlocklistCacheUp(): array
    {
        return $this->apiCache->warmUp();
    }

    /**
     * Used in stream mode only.
     * This method should be called periodically (ex: crontab) in an asynchronous way to update the bouncer cache.
     *
     * @return array Number of deleted and new decisions, and errors when processing decisions
     *
     * @throws InvalidArgumentException|\Psr\Cache\CacheException|BouncerException
     */
    public function refreshBlocklistCache(): array
    {
        return $this->apiCache->pullUpdates();
    }

    /**
     * This method clear the full data in cache.
     *
     * @return bool If the cache has been successfully cleared or not
     *
     * @throws InvalidArgumentException|BouncerException
     */
    public function clearCache(): bool
    {
        return $this->apiCache->clear();
    }

    /**
     * This method prune the cache: it removes all the expired cache items.
     *
     * @return bool If the cache has been successfully pruned or not
     * @throws BouncerException
     */
    public function pruneCache(): bool
    {
        return $this->apiCache->prune();
    }

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
     * Build a captcha couple.
     *
     * @return array an array composed of two items, a "phrase" string representing the phrase and a "inlineImage"
     *     representing the image data
     */
    public static function buildCaptchaCouple(): array
    {
        $captchaBuilder = new CaptchaBuilder();

        return [
            'phrase' => $captchaBuilder->getPhrase(),
            'inlineImage' => $captchaBuilder->build()->inline(),
        ];
    }

    /**
     * Check if the captcha filled by the user is correct or not.
     * We are permissive with the user (0 is interpreted as "o" and 1 in interpreted as "l").
     *
     * @param string $expected The expected phrase
     * @param string $try The phrase to check (the user input)
     * @param string $ip The IP of the use (for logging purpose)
     *
     * @return bool If the captcha input was correct or not
     */
    public function checkCaptcha(string $expected, string $try, string $ip): bool
    {
        $solved = PhraseBuilder::comparePhrases($expected, $try);
        $this->logger->warning('', [
            'type' => 'CAPTCHA_SOLVED',
            'ip' => $ip,
            'resolution' => $solved,
        ]);

        return $solved;
    }

    /**
     * Test the connection to the cache system (Redis or Memcached).
     *
     * @return void If the connection was successful or not
     *
     * @throws BouncerException|InvalidArgumentException if the connection was not successful
     * */
    public function testConnection()
    {
        $this->apiCache->testConnection();
    }

    public function getApiCache(): ApiCache
    {
        return $this->apiCache;
    }

    public function getCacheAdapter(): TagAwareAdapterInterface
    {
        return $this->getApiCache()->getAdapter();
    }

    public function getClient(): ApiClient
    {
        return $this->getApiCache()->getClient();
    }

    public function getRestClient(): ClientAbstract
    {
        return $this->getClient()->getRestClient();
    }
}
