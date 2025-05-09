/* eslint-disable no-undef */
const {
    addDecision,
    wait,
    goToAdmin,
    onAdminGoToSettingsPage,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    removeAllDecisions,
    fillInput,
    onLoginPageLoginAsAdmin,
    setDefaultConfig,
    banOwnIpForSeconds,
    selectByName,
    setToggle,
    goToPublicPage,
    deleteFileContent,
    getFileContent,
    runCacheAction,
    onAdvancedPageEnableUsageMetrics,
    onAdvancedPageDisableUsageMetrics,
    forceCronRun,
} = require("../utils/helpers");

const {
    CURRENT_IP,
    PROXY_IP,
    BOUNCER_KEY,
    BOUNCER_KEY_FILE,
    BOUNCER_CERT_FILE,
    AGENT_CERT_FILE,
    CA_CERT_FILE,
    DEBUG_LOG_PATH,
} = require("../utils/constants");

describe(`Run in Live mode`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
    });

    it("Should clear cache", async () => {
        // Enable and disable usage metrcis before all to make WP-cron working
        await runCacheAction("clear"); // To reset metrics
    });

    it("Should display a captcha wall instead of a ban wall in Flex mode", async () => {
        // set Flex mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "flex_bouncing",
        );
        await onAdminSaveSettings();

        await banOwnIpForSeconds(15 * 60, CURRENT_IP);

        // Should be a captcha wall
        await publicHomepageShouldBeCaptchaWall();
        // metrics: cscli/captcha = 1
    });

    it('Should be accessible in Disabled mode"', async () => {
        // set Disabled mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "bouncing_disabled",
        );
        await onAdminSaveSettings();

        // Should be accessible
        await publicHomepageShouldBeAccessible();
        // metrics: cscli/captcha = 1

        // Go back to normal mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "normal_bouncing",
        );
        await onAdminSaveSettings();

        // Should be a ban wall
        await publicHomepageShouldBeBanWall();
        // metrics: cscli/captcha = 1 | cscli/ban = 1
    });

    it('Should display back the homepage with no remediation"', async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
        // metrics: cscli/captcha = 1 | cscli/ban = 1 | clean/bypass = 1
    });

    it("Should fallback to the selected remediation for unknown remediation", async () => {
        await removeAllDecisions();
        await addDecision(CURRENT_IP, "mfa", 15 * 60);
        await wait(1000);
        await publicHomepageShouldBeCaptchaWall();
        // metrics: cscli/captcha = 2 | cscli/ban = 1 | clean/bypass = 1
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.selectOption(
            "[name=crowdsec_fallback_remediation]",
            "bypass",
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeAccessible();
        // metrics: cscli/captcha = 2 | cscli/ban = 1 | clean/bypass = 2
    });

    it("Should display metrics report in UI", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();

        await expect(page).toHaveText("#metrics-cscli-captcha", "captcha: 2");
        await expect(page).toHaveText("#metrics-cscli-ban", "ban: 1");
        await expect(page).toHaveText("#metrics-total-ban", "ban: 1");
        await expect(page).toHaveText("#metrics-total-captcha", "captcha: 2");
        // In multisite, it's 4, not sure why...(perhaps some admin pages is considered as "non admin" and bounced)
        await expect(page).toMatchText("#metrics-total-bypass", /bypass: 2|4/);
    });

    it("Should push usage metrics", async () => {
        await deleteFileContent(DEBUG_LOG_PATH);
        let logContent = await getFileContent(DEBUG_LOG_PATH);
        await expect(logContent).toBe("");
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableUsageMetrics();
        await onAdminSaveSettings();
        // Cron is set to 15 minutes, so we need to force execution
        await forceCronRun();
        logContent = await getFileContent(DEBUG_LOG_PATH);
        // metrics: cscli/captcha = 2 | cscli/ban = 1 | clean/bypass = 3
        // The log should contain processed count = 5 or 6, depending how WordPress handle its cron...
        // (if its 5, that means that there is another metrics call with processed = 1)
        // In multisite, it can be 7, not sure why...(perhaps some admin pages is considered as "non admin" and bounced)
        await expect(logContent).toMatch(
            new RegExp(
                `{"name":"dropped","value":2,"unit":"request","labels":{"origin":"cscli","remediation":"captcha"}},{"name":"dropped","value":1,"unit":"request","labels":{"origin":"cscli","remediation":"ban"}},{"name":"processed","value":(5|6|7),"unit":"request"}`,
            ),
        );

        await expect(logContent).not.toContain("No metrics to send");
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_push_usage_metrics");

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec usage metrics have just been pushed.",
        );
        await expect(page).toHaveText("#metrics-no-new", "No new metrics");

        // Disable usage metrics for future tests
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageDisableUsageMetrics();
        await onAdminSaveSettings();
    });

    it('Should handle X-Forwarded-For header for whitelisted IPs only"', async () => {
        await removeAllDecisions();
        await banIpForSeconds(CURRENT_IP, 15 * 60);

        // Remove PROXY IP from the CDN list (via a range)
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_trust_ip_forward_list", "");
        await onAdminSaveSettings();

        // Should be accessible as PROXY IP is not trust by CDN
        await publicHomepageShouldBeAccessible();

        // Add the current IP to the CDN list (via a range)
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_trust_ip_forward_list", `${PROXY_IP}/30`);
        await onAdminSaveSettings();

        // Should be banned
        await publicHomepageShouldBeBanWall();
    });
});

describe(`Test Display error`, () => {
    it("Should show errors", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();

        await fillInput("crowdsec_api_key", "bad_key");
        await onAdminSaveSettings();
        await goToPublicPage();
        await expect(page).toHaveText("body", "Fatal error");
    });

    it("Should not show errors", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_display_errors", false);
        await onAdminSaveSettings();
        await goToPublicPage();
        await expect(page).not.toHaveText("body", "Fatal error");

        // Reset good api key
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await fillInput("crowdsec_api_key", BOUNCER_KEY);
        await onAdminSaveSettings();
    });
});

describe(`Test TLS auth in Live mode`, () => {
    it("Should configure TLS", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await setToggle("crowdsec_use_curl", true);
        await selectByName("crowdsec_auth_type", "tls");

        await fillInput(
            "crowdsec_tls_key_path",
            `/var/www/html/${BOUNCER_KEY_FILE}`,
        );
        await setToggle("crowdsec_tls_verify_peer", true);
        await fillInput(
            "crowdsec_tls_ca_cert_path",
            `/var/www/html/${CA_CERT_FILE}`,
        );
        // Bad path
        await fillInput("crowdsec_tls_cert_path", "bad-path");
        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");

        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-error",
            /Technical error.*could not load PEM client certificate/,
        );
        // Bad cert
        await fillInput(
            "crowdsec_tls_cert_path",
            `/var/www/html/${AGENT_CERT_FILE}`,
        );
        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-error",
            /Technical error.*unable to set private key file/,
        );

        // Bad CA with verify peer
        await fillInput(
            "crowdsec_tls_cert_path",
            `/var/www/html/${BOUNCER_CERT_FILE}`,
        );
        await fillInput(
            "crowdsec_tls_ca_cert_path",
            `/var/www/html/${AGENT_CERT_FILE}`,
        );
        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-error",
            /Technical error.*unable to get local issuer certificate/,
        );

        // Bad CA without verify peer
        await setToggle("crowdsec_tls_verify_peer", false);
        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Bouncing has been successfully tested/,
        );

        // Good settings with curl
        await setToggle("crowdsec_tls_verify_peer", true);

        await fillInput(
            "crowdsec_tls_ca_cert_path",
            `/var/www/html/${CA_CERT_FILE}`,
        );
        await fillInput(
            "crowdsec_tls_cert_path",
            `/var/www/html/${BOUNCER_CERT_FILE}`,
        );

        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Bouncing has been successfully tested/,
        );

        // Good settings without curl
        await setToggle("crowdsec_use_curl", false);
        await onAdminSaveSettings();
        await page.click("#crowdsec_action_test_connection #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Bouncing has been successfully tested/,
        );
    }, 120000);

    it("Should display the homepage with no remediation", async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
    });

    it("Should display a ban wall", async () => {
        await banIpForSeconds(CURRENT_IP, 15 * 60);
        await publicHomepageShouldBeBanWall();
    });
});
