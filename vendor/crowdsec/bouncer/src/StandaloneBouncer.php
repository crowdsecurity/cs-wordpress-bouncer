<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

use CrowdSec\RemediationEngine\CacheStorage\CacheStorageException;
use CrowdSec\RemediationEngine\LapiRemediation;
use CrowdSec\RemediationEngine\Logger\FileLog;
use IPLib\Factory;
use Psr\Log\LoggerInterface;

/**
 * The class that apply a bounce in standalone mode.
 *
 * @author    CrowdSec team
 *
 * @see      https://crowdsec.net CrowdSec Official Website
 *
 * @copyright Copyright (c) 2021+ CrowdSec
 * @license   MIT License
 */
class StandaloneBouncer extends AbstractBouncer
{
    /**
     * @throws BouncerException
     * @throws CacheStorageException
     */
    public function __construct(array $configs, LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new FileLog($configs, 'php_standalone_bouncer');
        $configs = $this->handleTrustedIpsConfig($configs);
        $configs['user_agent_version'] = Constants::VERSION;
        $configs['user_agent_suffix'] = 'Standalone';
        $client = $this->handleClient($configs, $this->logger);
        $cache = $this->handleCache($configs, $this->logger);
        $remediation = new LapiRemediation($configs, $client, $cache, $this->logger);

        parent::__construct($configs, $remediation, $this->logger);
    }

    /**
     * The current HTTP method.
     */
    public function getHttpMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? "";
    }

    /**
     * @param string $name Ex: "X-Forwarded-For"
     */
    public function getHttpRequestHeader(string $name): ?string
    {
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($name));
        if (!\array_key_exists($headerName, $_SERVER)) {
            return null;
        }

        return is_string($_SERVER[$headerName]) ? $_SERVER[$headerName] : null;
    }

    /**
     * Get the value of a posted field.
     */
    public function getPostedVariable(string $name): ?string
    {
        if (!isset($_POST[$name])) {
            return null;
        }

        return is_string($_POST[$name]) ? $_POST[$name] : null;
    }

    /**
     * @return string The current IP, even if it's the IP of a proxy
     */
    public function getRemoteIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? "";
    }

    /**
     * The current URI.
     */
    public function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? "";
    }

    /**
     * The Standalone bouncer "trust_ip_forward_array" config accepts an array of IPs.
     * This method will return array of comparable IPs array
     *
     * @param array $configs // ['1.2.3.4']
     * @return array // [['001.002.003.004', '001.002.003.004']]
     * @throws BouncerException
     */
    private function handleTrustedIpsConfig(array $configs): array
    {
        // Convert array of string to array of array with comparable IPs
        if (isset($configs['trust_ip_forward_array']) && \is_array(($configs['trust_ip_forward_array']))) {
            $forwardConfigs = $configs['trust_ip_forward_array'];
            $finalForwardConfigs = [];
            foreach ($forwardConfigs as $forwardConfig) {
                if (!\is_string($forwardConfig)) {
                    throw new BouncerException("'trust_ip_forward_array' config must be an array of string");
                }
                $parsedString = Factory::parseAddressString($forwardConfig, 3);
                if (!empty($parsedString)) {
                    $comparableValue = $parsedString->getComparableString();
                    $finalForwardConfigs[] = [$comparableValue, $comparableValue];
                }
            }
            $configs['trust_ip_forward_array'] = $finalForwardConfigs;
        }

        return $configs;
    }
}
