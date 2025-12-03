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
    onAdvancedPageEnableUsageMetrics,
    getFileContent,
    deleteFileContent,
    onAdvancedPageDisableUsageMetrics,
    runCacheAction,
    wait,
} = require("../utils/helpers");

const { CURRENT_IP, DEBUG_LOG_PATH } = require("../utils/constants");

describe(`Run in Stream mode`, () => {
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

    it('Should enable the stream mode"', async () => {
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

    it("Should refresh the cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_refresh_cache");

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "The cache has just been refreshed",
        );
    });

    it('Should enable cURL"', async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await setToggle("crowdsec_use_curl", true);
        await onAdminSaveSettings();
    });

    it("Should display a ban wall via stream mode", async () => {
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        // metrics: cscli/ban = 2, clean/bypass = 1
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
        // metrics: cscli/ban = 2, clean/bypass = 2
    });

    it("Should refresh the cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_refresh_cache");

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "The cache has just been refreshed",
        );
    });

    it("Should push remediation metrics", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableUsageMetrics();
        await onAdminSaveSettings();
        await wait(2000);

        await page.click("#crowdsec_push_usage_metrics");
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec remediation metrics have just been pushed.",
        );

        const logContent = await getFileContent(DEBUG_LOG_PATH);
        // Extract and validate metric values are within expected range (>= 2 and <= 20)
        // Values may vary depending on WP-cron execution, multisite behavior, etc.
        const banMatch = logContent.match(
            /{"name":"dropped","value":(\d+),"unit":"request","labels":{"origin":"cscli","remediation":"ban"}}/,
        );
        expect(banMatch).not.toBeNull();
        const banValue = parseInt(banMatch[1], 10);
        expect(banValue).toBeGreaterThanOrEqual(2);
        expect(banValue).toBeLessThanOrEqual(20);

        const processedMatch = logContent.match(
            /{"name":"processed","value":(\d+),"unit":"request"}/,
        );
        expect(processedMatch).not.toBeNull();
        const processedValue = parseInt(processedMatch[1], 10);
        expect(processedValue).toBeGreaterThanOrEqual(2);
        expect(processedValue).toBeLessThanOrEqual(20);

        // Disable remediation metrics for future tests
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageDisableUsageMetrics();
        await onAdminSaveSettings();
    });
});
