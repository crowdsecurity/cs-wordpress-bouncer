<?php

declare(strict_types=1);

namespace CrowdSecBouncer;

require_once __DIR__.'/templates/captcha.php';
require_once __DIR__.'/templates/access-forbidden.php';

use Exception;
use IPLib\Factory;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;

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
abstract class AbstractBounce
{
    /** @var array */
    protected $settings = [];

    /** @var bool */
    protected $debug = false;

    /** @var bool */
    protected $displayErrors = false;

    /** @var LoggerInterface */
    protected $logger;

    /** @var Bouncer */
    protected $bouncer;

    protected function getIntegerSettings(string $name): int
    {
        return !empty($this->settings[$name]) ? (int) $this->settings[$name] : 0;
    }

    protected function getBoolSettings(string $name): bool
    {
        return !empty($this->settings[$name]) && $this->settings[$name];
    }

    protected function getStringSettings(string $name): string
    {
        return !empty($this->settings[$name]) ? (string) $this->settings[$name] : '';
    }

    protected function getArraySettings(string $name): array
    {
        return !empty($this->settings[$name]) ? (array) $this->settings[$name] : [];
    }

    /**
     * Run a bounce.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function run(
    ): void {
        if ($this->shouldBounceCurrentIp()) {
            $this->bounceCurrentIp();
        }
    }

    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    public function setDisplayErrors(bool $displayErrors)
    {
        $this->displayErrors = $displayErrors;
    }

    protected function initLoggerHelper($logDirectoryPath, $loggerName): void
    {
        // Singleton for this function
        if ($this->logger) {
            return;
        }

        $this->logger = new Logger($loggerName);
        $logPath = $logDirectoryPath.'/prod.log';
        $fileHandler = new RotatingFileHandler($logPath, 0, Logger::INFO);
        $fileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
        $this->logger->pushHandler($fileHandler);

        // Set custom readable logger when debug=true
        if ($this->debug) {
            $debugLogPath = $logDirectoryPath.'/debug.log';
            $debugFileHandler = new RotatingFileHandler($debugLogPath, 0, Logger::DEBUG);
            $debugFileHandler->setFormatter(new LineFormatter("%datetime%|%level%|%context%\n"));
            $this->logger->pushHandler($debugFileHandler);
        }
    }

    /**
     * @throws Exception|InvalidArgumentException
     */
    protected function bounceCurrentIp()
    {
        $ip = $this->getRemoteIp();
        // X-Forwarded-For override
        $XForwardedForHeader = $this->getHttpRequestHeader('X-Forwarded-For');
        if (null !== $XForwardedForHeader) {
            $ipList = array_map('trim', array_values(array_filter(explode(',', $XForwardedForHeader))));
            $forwardedIp = end($ipList);
            if ($this->shouldTrustXforwardedFor($ip)) {
                $ip = $forwardedIp;
            } else {
                $this->logger->warning('', [
                'type' => 'NON_AUTHORIZED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
                'x_forwarded_for_ip' => $forwardedIp,
            ]);
            }
        }

        try {
            if (!$this->bouncer) {
                throw new BouncerException('Bouncer must be instantiated to bounce an IP.');
            }
            $ipToCheck = !empty($this->settings['forced_test_ip']) ? $this->settings['forced_test_ip'] : $ip;
            $remediation = $this->bouncer->getRemediationForIp($ipToCheck);
            $this->handleRemediation($remediation, $ipToCheck);
        } catch (Exception $e) {
            $this->logger->warning('', [
                'type' => 'UNKNOWN_EXCEPTION_WHILE_BOUNCING',
                'ip' => $ip,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            if ($this->displayErrors) {
                throw $e;
            }
        }
    }

    protected function shouldTrustXforwardedFor(string $ip): bool
    {
        $comparableAddress = Factory::parseAddressString($ip, 3)->getComparableString();
        if (null === $comparableAddress) {
            $this->logger->warning('', [
                'type' => 'INVALID_INPUT_IP',
                'ip' => $ip,
            ]);

            return false;
        }

        foreach ($this->getTrustForwardedIpBoundsList() as $comparableIpBounds) {
            if ($comparableAddress >= $comparableIpBounds[0] && $comparableAddress <= $comparableIpBounds[1]) {
                return true;
            }
        }

        return false;
    }

    protected function displayCaptchaWall()
    {
        $options = $this->getCaptchaWallOptions();
        $body = Bouncer::getCaptchaHtmlTemplate(
            $this->getSessionVariable('crowdsec_captcha_resolution_failed'),
            $this->getSessionVariable('crowdsec_captcha_inline_image'),
            '',
            $options
        );
        $this->sendResponse($body, 401);
    }

    protected function handleBanRemediation()
    {
        $options = $this->getBanWallOptions();
        $body = Bouncer::getAccessForbiddenHtmlTemplate($options);
        $this->sendResponse($body, 403);
    }

    protected function storeNewCaptchaCoupleInSession()
    {
        $captchaCouple = Bouncer::buildCaptchaCouple();
        $this->setSessionVariable('crowdsec_captcha_phrase_to_guess', $captchaCouple['phrase']);
        $this->setSessionVariable('crowdsec_captcha_inline_image', $captchaCouple['inlineImage']);
    }

    protected function clearCaptchaSessionContext()
    {
        $this->unsetSessionVariable('crowdsec_captcha_has_to_be_resolved');
        $this->unsetSessionVariable('crowdsec_captcha_phrase_to_guess');
        $this->unsetSessionVariable('crowdsec_captcha_inline_image');
        $this->unsetSessionVariable('crowdsec_captcha_resolution_failed');
    }

    /**
     * @return void
     */
    protected function handleCaptchaResolutionForm(string $ip)
    {
        // Early return if no captcha has to be resolved or if captcha already resolved.
        if (\in_array($this->getSessionVariable('crowdsec_captcha_has_to_be_resolved'), [null, false])) {
            return;
        }

        // Early return if no form captcha form has been filled.
        if ('POST' !== $this->getHttpMethod() || null === $this->getPostedVariable('crowdsec_captcha')) {
            return;
        }

        // Handle image refresh.
        if (null !== $this->getPostedVariable('refresh') && (int) $this->getPostedVariable('refresh')) {
            // Generate new captcha image for the user
            $this->storeNewCaptchaCoupleInSession();
            $this->setSessionVariable('crowdsec_captcha_resolution_failed', false);

            return;
        }

        // Handle a captcha resolution try
        if (null !== $this->getPostedVariable('phrase') && null !== $this->getSessionVariable('crowdsec_captcha_phrase_to_guess')) {
            if (!$this->bouncer) {
                throw new BouncerException('Bouncer must be instantiated to check captcha.');
            }
            if ($this->bouncer->checkCaptcha(
                $this->getSessionVariable('crowdsec_captcha_phrase_to_guess'),
                $this->getPostedVariable('phrase'),
                $ip)) {
                // User has correctly filled the captcha
                $this->setSessionVariable('crowdsec_captcha_has_to_be_resolved', false);
                $this->unsetSessionVariable('crowdsec_captcha_phrase_to_guess');
                $this->unsetSessionVariable('crowdsec_captcha_inline_image');
                $this->unsetSessionVariable('crowdsec_captcha_resolution_failed');
                $redirect = $this->getSessionVariable('crowdsec_captcha_resolution_redirect') ?? '/';
                $this->unsetSessionVariable('crowdsec_captcha_resolution_redirect');
                header("Location: $redirect");
                exit(0);
            } else {
                // The user failed to resolve the captcha.
                $this->setSessionVariable('crowdsec_captcha_resolution_failed', true);
            }
        }
    }

    /**
     * @param $ip
     *
     * @return void
     */
    protected function handleCaptchaRemediation($ip)
    {
        // Check captcha resolution form
        $this->handleCaptchaResolutionForm($ip);

        if (null === $this->getSessionVariable('crowdsec_captcha_has_to_be_resolved')) {
            // Set up the first captcha remediation.
            $this->storeNewCaptchaCoupleInSession();
            $this->setSessionVariable('crowdsec_captcha_has_to_be_resolved', true);
            $this->setSessionVariable('crowdsec_captcha_resolution_failed', false);
            $this->setSessionVariable('crowdsec_captcha_resolution_redirect', 'POST' === $this->getHttpMethod() &&
                                                                              !empty($_SERVER['HTTP_REFERER']) ?
                $_SERVER['HTTP_REFERER'] : '/');
        }

        // Display captcha page if this is required.
        if ($this->getSessionVariable('crowdsec_captcha_has_to_be_resolved')) {
            $this->displayCaptchaWall();
        }
    }

    /**
     * @return void
     */
    protected function handleRemediation(string $remediation, string $ip)
    {
        if (Constants::REMEDIATION_CAPTCHA !== $remediation && null !== $this->getSessionVariable('crowdsec_captcha_has_to_be_resolved')) {
            $this->clearCaptchaSessionContext();
        }
        switch ($remediation) {
            case Constants::REMEDIATION_CAPTCHA:
                $this->handleCaptchaRemediation($ip);
                break;
            case Constants::REMEDIATION_BAN:
                $this->handleBanRemediation();
                break;
            case Constants::REMEDIATION_BYPASS:
            default:
        }
    }
}
