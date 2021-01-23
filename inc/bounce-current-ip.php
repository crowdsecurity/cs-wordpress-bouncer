<?php

use CrowdSecBouncer\Bouncer;
use CrowdSecBouncer\Constants;
use IPLib\Factory;

function bounceCurrentIp()
{
    function shouldTrustXforwardedFor(string $ip): bool
    {
        $comparableAddress = Factory::addressFromString($ip)->getComparableString();
        foreach (get_option('crowdsec_trust_ip_forward_array') as $comparableIpBounds) {
            if ($comparableAddress >= $comparableIpBounds[0] && $comparableAddress <= $comparableIpBounds[1]) {
                return true;
            }
        }

        return false;
    }

    $ip = $_SERVER['REMOTE_ADDR'];

    // X-Forwarded-For override
    if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $ipList = array_map('trim', array_values(array_filter(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']))));
        $forwardedIp = end($ipList);
        if (shouldTrustXforwardedFor($ip)) {
            $ip = $forwardedIp;
        } else {
            getCrowdSecLoggerInstance()->warning('', [
                'type' => 'WP_NON_AUTHORIZED_X_FORWARDED_FOR_USAGE',
                'original_ip' => $ip,
                'x_forwarded_for_ip' => $forwardedIp,
            ]);
        }
    }

    function displayCaptchaWall()
    {
        header('HTTP/1.0 401 Unauthorized');
        $config = [
            'hide_crowdsec_mentions' => (bool) get_option('crowdsec_hide_mentions'),
            'color' => [
              'text' => [
                'primary' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_primary'), \ENT_QUOTES),
                'secondary' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_secondary'), \ENT_QUOTES),
                'button' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_button'), \ENT_QUOTES),
                'error_message' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_error_message'), \ENT_QUOTES),
              ],
              'background' => [
                'page' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_page'), \ENT_QUOTES),
                'container' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_container'), \ENT_QUOTES),
                'button' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_button'), \ENT_QUOTES),
                'button_hover' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_button_hover'), \ENT_QUOTES),
              ],
            ],
            'text' => [
              'captcha_wall' => [
                'tab_title' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_tab_title'), \ENT_QUOTES),
                'title' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_title'), \ENT_QUOTES),
                'subtitle' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_subtitle'), \ENT_QUOTES),
                'refresh_image_link' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_refresh_image_link'), \ENT_QUOTES),
                'captcha_placeholder' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_captcha_placeholder'), \ENT_QUOTES),
                'send_button' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_send_button'), \ENT_QUOTES),
                'error_message' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_error_message'), \ENT_QUOTES),
                'footer' => wp_specialchars_decode(get_option('crowdsec_theme_text_captcha_wall_footer'), \ENT_QUOTES),
              ],
            ],
            'custom_css' => get_option('crowdsec_theme_custom_css'),
          ];
        echo Bouncer::getCaptchaHtmlTemplate($_SESSION['crowdsec_captcha_resolution_failed'], $_SESSION['crowdsec_captcha_inline_image'], '', $config);
        die();
    }

    function handleBanRemediation()
    {
        header('HTTP/1.0 403 Forbidden');
        $config = [
            'hide_crowdsec_mentions' => (bool) get_option('crowdsec_hide_mentions'),
            'color' => [
              'text' => [
                'primary' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_primary', \ENT_QUOTES)),
                'secondary' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_secondary', \ENT_QUOTES)),
                'error_message' => wp_specialchars_decode(get_option('crowdsec_theme_color_text_error_message', \ENT_QUOTES)),
              ],
              'background' => [
                'page' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_page', \ENT_QUOTES)),
                'container' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_container', \ENT_QUOTES)),
                'button' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_button', \ENT_QUOTES)),
                'button_hover' => wp_specialchars_decode(get_option('crowdsec_theme_color_background_button_hover', \ENT_QUOTES)),
              ],
            ],
            'text' => [
              'ban_wall' => [
                'tab_title' => wp_specialchars_decode(get_option('crowdsec_theme_text_ban_wall_tab_title', \ENT_QUOTES)),
                'title' => wp_specialchars_decode(get_option('crowdsec_theme_text_ban_wall_title', \ENT_QUOTES)),
                'subtitle' => wp_specialchars_decode(get_option('crowdsec_theme_text_ban_wall_subtitle', \ENT_QUOTES)),
                'footer' => wp_specialchars_decode(get_option('crowdsec_theme_text_ban_wall_footer', \ENT_QUOTES)),
              ],
            ],
            'custom_css' => wp_specialchars_decode(get_option('crowdsec_theme_custom_css', \ENT_QUOTES)),
          ];
        echo Bouncer::getAccessForbiddenHtmlTemplate($config);
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
        if (!isset($_SESSION['crowdsec_captcha_has_to_be_resolved'])) {
            return;
        }

        // Captcha already resolved.
        if (!$_SESSION['crowdsec_captcha_has_to_be_resolved']) {
            return;
        }

        // Early return if no form captcha form has been filled.
        if (('POST' !== $_SERVER['REQUEST_METHOD'] || !isset($_POST['crowdsec_captcha']))) {
            return;
        }

        // Handle image refresh.
        if (isset($_POST['refresh']) && (bool) (int) $_POST['refresh']) {
            // Generate new captcha image for the user
            storeNewCaptchaCoupleInSession();
            $_SESSION['crowdsec_captcha_resolution_failed'] = false;

            return;
        }

        // Handle a captcha resolution try
        if (isset($_POST['phrase'])) {
            $bouncer = getBouncerInstance();
            if ($bouncer->checkCaptcha($_SESSION['crowdsec_captcha_phrase_to_guess'], $_POST['phrase'], $ip)) {
                // User has correctly fill the captcha

                $_SESSION['crowdsec_captcha_has_to_be_resolved'] = false;
                unset($_SESSION['crowdsec_captcha_phrase_to_guess']);
                unset($_SESSION['crowdsec_captcha_inline_image']);
                unset($_SESSION['crowdsec_captcha_resolution_failed']);
            } else {
                // The user failed to resolve the captcha.

                $_SESSION['crowdsec_captcha_resolution_failed'] = true;
            }
        }
    }

    function handleCaptchaRemediation($ip)
    {
        // Check captcha resolution form
        handleCaptchaResolutionForm($ip);

        if (!isset($_SESSION['crowdsec_captcha_has_to_be_resolved'])) {
            // Setup the first captcha remediation.

            storeNewCaptchaCoupleInSession();
            $_SESSION['crowdsec_captcha_has_to_be_resolved'] = true;
            $_SESSION['crowdsec_captcha_resolution_failed'] = false;
        }

        // Display captcha page if this is required.
        if ($_SESSION['crowdsec_captcha_has_to_be_resolved']) {
            displayCaptchaWall();
        }
    }

    function handleRemediation(string $remediation, string $ip)
    {
        if (Constants::REMEDIATION_CAPTCHA !== $remediation && isset($_SESSION['crowdsec_captcha_has_to_be_resolved'])) {
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

    $bouncingLevel = esc_attr(get_option('crowdsec_bouncing_level'));
    $shouldBounce = (CROWDSEC_BOUNCING_LEVEL_DISABLED !== $bouncingLevel);

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
        if ('/favicon.ico' === $_SERVER['REQUEST_URI']) {
            return;
        }

        $everywhere = empty(get_option('crowdsec_public_website_only'));
        $shoudRun = ($everywhere || (!is_admin() && !in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-cron.php'])));
        if ($shoudRun && isBouncerConfigOk()) {
            bounceCurrentIp();
        }
        restore_error_handler();
    } catch (\Exception $e) {
        getCrowdSecLoggerInstance()->error('', [
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
