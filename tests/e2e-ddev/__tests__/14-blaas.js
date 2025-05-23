const {
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminGoToSettingsPage,
    onAdminSaveSettings,
    onAdvancedPageEnableStreamMode,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    removeAllDecisions,
    forceCronRun,
    onLoginPageLoginAsAdmin,
    setDefaultConfig,
    setToggle,
    fillInput,
    onAdvancedPageEnableUsageMetrics,
    deleteFileContent,
    onAdvancedPageDisableUsageMetrics,
    runCacheAction,
    selectByName,
} = require("../utils/helpers");

const {
    CURRENT_IP,
    DEBUG_LOG_PATH,
    FAKE_BLAAS_URL,
    BOUNCER_KEY_FILE,
    CA_CERT_FILE,
    BOUNCER_CERT_FILE,
    MULTISITE,
} = require("../utils/constants");

describe("Check BLaaS URL behavior", () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
    });

    it("Should activate WP-CRON", async () => {
        // Enable and disable remediation metrics before all to make WP-cron working
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableUsageMetrics();
        await onAdminSaveSettings(false);
        await onAdvancedPageDisableUsageMetrics();
        await onAdminSaveSettings();
        await runCacheAction("clear"); // To reset metrics
    });

    it("Should enable the stream mode", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableStreamMode();
        await onAdminSaveSettings();
        await deleteFileContent(DEBUG_LOG_PATH);
    });

    it("Should display a ban wall via stream mode", async () => {
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        // metrics: cscli/ban = 1
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
        // metrics: cscli/ban = 1, clean/bypass = 1
    });

    it("Should set a Blass URL", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await fillInput("crowdsec_api_url", FAKE_BLAAS_URL);
        await onAdminSaveSettings(false);
        // There can be other warning like "New WP version available"
        const warnings = await page
            .locator(".notice-warning")
            .allTextContents();
        const found = warnings.some((text) =>
            text.includes('You have just defined a "Block As A Service" URL'),
        );
        expect(found).toBe(true);
    });

    it("Should block TLS auth", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
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
        await fillInput(
            "crowdsec_tls_cert_path",
            `/var/www/html/${BOUNCER_CERT_FILE}`,
        );

        await onAdminSaveSettings(false);
        await expect(page).toHaveText(
            ".notice-error",
            "Rolling back to Bouncer API key authentication",
        );
    });

    it("Should block Live Mode", async () => {
        if (MULTISITE) {
            console.warn(
                "In multisite mode, deactivation callback for checkboxes doesn't work, so we skip this test",
            );
        } else {
            await goToAdmin();
            await onAdminGoToAdvancedPage();
            await setToggle("crowdsec_stream_mode", false);

            await onAdminSaveSettings(false);
            await expect(page).toHaveText(
                ".notice-error",
                "Rolling back to Stream mode.",
            );
        }
    });

    it("Should block remediation metrics", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_usage_metrics", true);

        await onAdminSaveSettings(false);
        await expect(page).toHaveText(
            ".notice-error",
            `Pushing remediation metrics with a Block as a Service LAPI (${FAKE_BLAAS_URL}) is not supported.`,
        );
    });

    it('Should block AppSec"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_use_appsec", true);

        await onAdminSaveSettings(false);
        await expect(page).toHaveText(
            ".notice-error",
            `Using AppSec with a Block as a Service LAPI (${FAKE_BLAAS_URL}) is not supported.`,
        );
    });

    it("Should interact with remediation metrics", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await expect(page).toHaveText("#metrics-cscli-ban", "ban: 1");
        await expect(page).toHaveText("#metrics-total-ban", "ban: 1");
        const count = await page
            .locator("#crowdsec_push_usage_metrics")
            .count();
        await expect(count).toBe(0);

        await page.click("#crowdsec_reset_usage_metrics");

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec remediation metrics have been reset successfully.",
        );
        await expect(page).toHaveText("#metrics-no-new", "No new metrics");
    });
});
