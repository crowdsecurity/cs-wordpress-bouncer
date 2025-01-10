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
} = require("../utils/helpers");

const { CURRENT_IP, DEBUG_LOG_PATH } = require("../utils/constants");

describe(`Run in Stream mode`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
        await runCacheAction("clear"); // To reset metrics
    });

    it('Should enable the stream mode"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableStreamMode();
        await onAdminSaveSettings();
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

    it("Should push usage metrics", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableUsageMetrics();
        await onAdminSaveSettings();
        await deleteFileContent(DEBUG_LOG_PATH);

        await page.click("#crowdsec_push_usage_metrics");
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec usage metrics have just been pushed.",
        );

        const logContent = await getFileContent(DEBUG_LOG_PATH);

        await expect(logContent).toMatch(
            new RegExp(
                `{"name":"dropped","value":2,"unit":"request","labels":{"origin":"cscli","remediation":"ban"}},{"name":"processed","value":4,"unit":"request"}`,
            ),
        );

        // Disable usage metrics for future tests
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageDisableUsageMetrics();
        await onAdminSaveSettings();
    });
});
