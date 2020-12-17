<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;

// Captcha repeat delay in seconds
define('CROWDSEC_CAPTCHA_REPEAT_MIN_DELAY', 15 * 60); // TODO P3 Dynamize this value


function bounceCurrentIp()
{
    $ip = $_SERVER["REMOTE_ADDR"];

    function displayCaptchaPage($ip, $error = false)
    {
        if (!isset($_SESSION['phrase'])) {
            $captchaCouple = Bouncer::buildCaptchaCouple();
            $_SESSION['phrase'] = $captchaCouple['phrase'];
            $_SESSION['img'] = $captchaCouple['inlineImage'];
        }
        $captchaImageSrc = $_SESSION['img'];
        $captchaResolutionFormUrl = '';
        echo Bouncer::getCaptchaHtmlTemplate($error, $captchaImageSrc, $captchaResolutionFormUrl);
        die();
    }

    function handleBanRemediation(Bouncer $bouncer, $ip)
    {
        // TODO P3 make a function instead of this
        header('HTTP/1.0 403 Forbidden');
        echo Bouncer::getAccessForbiddenHtmlTemplate();
        die();
    }

    function checkCaptcha(string $ip)
    {
        //error_log("crowdsec-wp: " . $ip . " is in captcha mode"); TODO P2 check how 

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crowdsec_captcha'])) {

            // Handle image refresh.
            $refreshImage = (isset($_POST['refresh']) && (bool)(int)$_POST['refresh']);

            if ($refreshImage) {
                // generate new image
                $captchaCouple = Bouncer::buildCaptchaCouple();
                $_SESSION['phrase'] = $captchaCouple['phrase'];
                $_SESSION['img'] = $captchaCouple['inlineImage'];

                // display captcha page
                $_SESSION["captchaResolved"] = false;
                displayCaptchaPage($ip, true);
            }


            // Handle captcha resolve.
            $bouncer = getBouncerInstance();
            $captchaCorrectlyFilled = (isset($_POST['phrase']) && $bouncer->checkCaptcha($_SESSION['phrase'], $_POST['phrase'], $ip));
            if ($captchaCorrectlyFilled) {
                $_SESSION["captchaResolved"] = true;
                $_SESSION["captchaResolvedAt"] = time();
                unset($_SESSION['phrase']);
                return;
            }
        }
        $_SESSION["captchaResolved"] = false;
        displayCaptchaPage($ip, true);
    }

    function handleCaptchaRemediation(string $ip)
    {
        // Never displayed to user.
        if (!isset($_SESSION["captchaResolved"])) {
            displayCaptchaPage($ip);
        }
        // User was unable to resolve.
        if (!$_SESSION["captchaResolved"]) {
            displayCaptchaPage($ip);
        }


        // User resolved too long ago.
        $resolvedTooLongAgo = ((time() - $_SESSION["captchaResolvedAt"]) > CROWDSEC_CAPTCHA_REPEAT_MIN_DELAY);
        if ($resolvedTooLongAgo) {
            displayCaptchaPage($ip);
        }
    }

    function handleRemediation(string $remediation, string $ip, Bouncer $bouncer)
    {
        switch ($remediation) {
            case Constants::REMEDIATION_BYPASS:
                return;
            case Constants::REMEDIATION_CAPTCHA:
                handleCaptchaRemediation($ip);
                break;
            case Constants::REMEDIATION_BAN:
                handleBanRemediation($bouncer, $ip);
        }
    }

    // Control Captcha
    if (isset($_SESSION['phrase'])) {
        checkCaptcha($ip);
    }

    $bouncingLevel = esc_attr(get_option("crowdsec_bouncing_level"));
    $shouldBounce = ($bouncingLevel !== CROWDSEC_BOUNCING_LEVEL_DISABLED);

    if ($shouldBounce) {
        try {
            $bouncer = getBouncerInstance();
            $remediation = $bouncer->getRemediationForIp($ip);
            handleRemediation($remediation, $ip, $bouncer);
        } catch (WordpressCrowdSecBouncerException $e) {
            // TODO log error for debug mode only.
        }
    }
}

/**
 * If there is any technical problem while bouncing, don't block the user. Bypass boucing and log the error.
 */
function safelyBounceCurrentIp()
{
    // TODO P3 check that every kind of errors are catched.
    try {
        // avoid useless bouncing
        if ($_SERVER['REQUEST_URI'] !== '/favicon.ico') {
            return;
        }

        $everywhere = empty(get_option('crowdsec_public_website_only'));
        $shoudRun = ($everywhere || !is_admin());
        if ($shoudRun) {
            bounceCurrentIp();
        }
    } catch (\Exception $e) {
        if (WP_DEBUG) {
            throw $e;
        }
        // TODO P3 log error if something has been catched here
    }
}
