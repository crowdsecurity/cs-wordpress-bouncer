/* eslint-disable no-undef */
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
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Run in Stream mode`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
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
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
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
    });

    it("Should display back the homepage with no remediation via stream mode", async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
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
});
