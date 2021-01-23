const {
    notify,
    wait,
    waitForNavigation,
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo,
    onAdminAdvancedSettingsPageSetBadIpCacheDurationTo,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    removeAllDecisions,
    loadCookies,
} = require("../utils/helpers");

let detectedBrowserIp;

describe(`Run in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        detectedBrowserIp = await page.$eval("#detected_ip_address", el => el.textContent);
    });

    it("Should prune the File system cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_prune_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec cache has just been pruned."
        );
    });

    it("Should clear the cache on demand", async () => {
        await onAdminGoToAdvancedPage();
        await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(60);
        await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(60);
        await onAdminSaveSettings();
        await banOwnIpForSeconds(15 * 60, detectedBrowserIp);
        await publicHomepageShouldBeBanWall();
        wait(2000);
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        wait(2000);
        await publicHomepageShouldBeBanWall();

        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.on("dialog", async (dialog) => {
            await dialog.accept();
        });
        await page.click("#crowdsec_clear_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec cache has just been cleared."
        );
        await publicHomepageShouldBeAccessible();
    });
});
