<?php

use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;


function bounceCurrentIp()
{
    $ip = $_SERVER["REMOTE_ADDR"];

    function displayCaptchaPage($ip, $error = false)
    {
        $captcha = new CaptchaBuilder;
        $_SESSION['phrase'] = $captcha->getPhrase();
        $img = $captcha->build()->inline();
        require_once(__DIR__ . "/templates/remediations/captcha.php");
        die();
    }

    function handleBanRemediation(Bouncer $bouncer, $ip)
    {
        die($bouncer->getDefault403Template());
    }

    function checkCaptcha(string $ip)
    {
        //error_log("crowdsec-wp: " . $ip . " is in captcha mode"); TODO P2 check how 

        $captchaCorrectlyFilled = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phrase']) && PhraseBuilder::comparePhrases($_SESSION['phrase'], $_POST['phrase']));
        if (!$captchaCorrectlyFilled) {
            $_SESSION["captchaResolved"] = false;
            displayCaptchaPage($ip, true);
        }

        $_SESSION["captchaResolved"] = true;
        unset($_SESSION['phrase']);
    }

    function handleCaptchaRemediation(string $ip)
    {
        if (!isset($_SESSION["captchaResolved"]) || !$_SESSION["captchaResolved"]) {
            displayCaptchaPage($ip);
        }
        // TODO P3 handle the case the user fill a captcha then the remediation expires then a new captcha remediation is asked, while the PHP session is active no captcha will never be ask again.
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
