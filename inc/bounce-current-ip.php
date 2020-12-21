<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;


function bounceCurrentIp()
{
    $ip = $_SERVER["REMOTE_ADDR"];

    function displayCaptchaWall()
    {
        header('HTTP/1.0 401 Unauthorized');
        echo Bouncer::getCaptchaHtmlTemplate($_SESSION["crowdsec_captcha_resolution_failed"], $_SESSION['crowdsec_captcha_inline_image'], '', !get_option('crowdsec_hide_mentions'));
        die();
    }

    function handleBanRemediation()
    {
        header('HTTP/1.0 403 Forbidden');
        echo Bouncer::getAccessForbiddenHtmlTemplate(!get_option('crowdsec_hide_mentions'));
        die();
    }

    function storeNewCaptchaCoupleInSession()
    {
        $captchaCouple = Bouncer::buildCaptchaCouple();
        $_SESSION['crowdsec_captcha_phrase_to_guess'] = $captchaCouple['phrase'];
        $_SESSION['crowdsec_captcha_inline_image'] = $captchaCouple['inlineImage'];
    }

    function clearCaptchaSessionContext()
    {
        unset($_SESSION['crowdsec_captcha_has_to_be_resolved']);
        unset($_SESSION['crowdsec_captcha_phrase_to_guess']);
        unset($_SESSION['crowdsec_captcha_inline_image']);
        unset($_SESSION['crowdsec_captcha_resolution_failed']);
    }

    function handleCaptchaResolutionForm(string $ip)
    {
        // Early return if no captcha has to be resolved.
        if (!isset($_SESSION["crowdsec_captcha_has_to_be_resolved"])) {
            return;
        }

        // Captcha already resolved.
        if (!$_SESSION["crowdsec_captcha_has_to_be_resolved"]) {
            return;
        }

        // Early return if no form captcha form has been filled.
        if (($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['crowdsec_captcha']))) {
            return;
        }

        // Handle image refresh.
        if (isset($_POST['refresh']) && (bool)(int)$_POST['refresh']) {
            // Generate new captcha image for the user
            storeNewCaptchaCoupleInSession();
            $_SESSION["crowdsec_captcha_resolution_failed"] = false;
            return;
        }

        // Handle a captcha resolution try
        if (isset($_POST['phrase'])) {
            $bouncer = getBouncerInstance();
            if ($bouncer->checkCaptcha($_SESSION['crowdsec_captcha_phrase_to_guess'], $_POST['phrase'], $ip)) {

                // User has correctly fill the captcha

                $_SESSION["crowdsec_captcha_has_to_be_resolved"] = false;
                unset($_SESSION['crowdsec_captcha_phrase_to_guess']);
                unset($_SESSION['crowdsec_captcha_inline_image']);
                unset($_SESSION['crowdsec_captcha_resolution_failed']);
            } else {

                // The user failed to resolve the captcha.

                $_SESSION["crowdsec_captcha_resolution_failed"] = true;
            }
        }
    }

    function handleCaptchaRemediation($ip)
    {

        // Check captcha resolution form
        handleCaptchaResolutionForm($ip);

        if (!isset($_SESSION["crowdsec_captcha_has_to_be_resolved"])) {

            // Setup the first captcha remediation.

            storeNewCaptchaCoupleInSession();
            $_SESSION["crowdsec_captcha_has_to_be_resolved"] = true;
            $_SESSION["crowdsec_captcha_resolution_failed"] = false;
        }

        // Display captcha page if this is required.
        if ($_SESSION["crowdsec_captcha_has_to_be_resolved"]) {
            displayCaptchaWall();
        }
    }

    function handleRemediation(string $remediation, string $ip)
    {
        if ($remediation !== Constants::REMEDIATION_CAPTCHA && isset($_SESSION["crowdsec_captcha_has_to_be_resolved"])) {
            clearCaptchaSessionContext();
        }
        switch ($remediation) {
            case Constants::REMEDIATION_BYPASS:
                return;
            case Constants::REMEDIATION_CAPTCHA:
                handleCaptchaRemediation($ip);
                break;
            case Constants::REMEDIATION_BAN:
                handleBanRemediation();
        }
    }

    $bouncingLevel = esc_attr(get_option("crowdsec_bouncing_level"));
    $shouldBounce = ($bouncingLevel !== CROWDSEC_BOUNCING_LEVEL_DISABLED);

    if ($shouldBounce) {
        try {
            $bouncer = getBouncerInstance();
            $remediation = $bouncer->getRemediationForIp($ip);
            handleRemediation($remediation, $ip);
        } catch (WordpressCrowdSecBouncerException $e) {
            
        }
    }
}

/**
 * If there is any technical problem while bouncing, don't block the user. Bypass boucing and log the error.
 */
function safelyBounceCurrentIp()
{
    try {
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        });
        // avoid useless bouncing
        if ($_SERVER['REQUEST_URI'] === '/favicon.ico') {
            return;
        }

        $everywhere = empty(get_option('crowdsec_public_website_only'));
        $shoudRun = ($everywhere || !is_admin());
        if ($shoudRun) {
            bounceCurrentIp();
        }
        restore_error_handler();
    } catch (\Exception $e) {
        getCrowdSecLoggerInstance()->error(null, [
            'type' => 'WP_EXCEPTION_WHILE_BOUNCING',
            'messsage' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        if (WP_DEBUG) {
            throw $e;
        }
    }
}
