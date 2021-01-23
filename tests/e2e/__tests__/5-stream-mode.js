const {
    notify,
    waitForNavigation,
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    onAdvancedPageEnableStreamMode,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    removeAllDecisions,
    forceCronRun,
    loadCookies,
} = require("../utils/helpers");

let detectedBrowserIp;

describe(`Run in Stream mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        detectedBrowserIp = await page.$eval("#detected_ip_address", el => el.textContent);
    });

    it('Should enable the stream mode"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableStreamMode();
        await onAdminSaveSettings();
    });

    it('Should display a ban wall via stream mode"', async () => {
        await banOwnIpForSeconds(15 * 60, detectedBrowserIp);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
    });

    it('Should display back the homepage with no remediation via stream mode"', async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });

    it("Should refresh the cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_refresh_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "The cache has just been refreshed (0 new decision, 0 deleted)."
        );
    });
});

/*

# Stream mode: Resync decisions each

Remove all decisions + Ban current IP during 15min
Set stream mode with 15 seconds resync
Refresh cache
(to finish writing)

# Recheck clean IP (to write)

# Recheck Bad IP (to write)

*/
