const {
    notify,
    wait,
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    removeAllDecisions,
    forceCronRun,
    fillInput,
    loadCookies,
} = require("../utils/helpers");

let detectedBrowserIp;

describe(`Use Memcached technology`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(async () => {
        await loadCookies(context);
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        detectedBrowserIp = await page.$eval("#detected_ip_address", el => el.textContent);
    });

    it('Should be able to use Memcached cache"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.selectOption("[name=crowdsec_cache_system]", "memcached");
        await wait(200);
        await fillInput(
            "crowdsec_memcached_dsn",
            "memcached://memcached:11211"
        );

        // TODO test bad DSN format and test DSN down

        await onAdminSaveSettings();
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "As the stream mode is enabled, the cache has just been warmed up, there is now 0 decision in cache."
        );

        await publicHomepageShouldBeAccessible();
        await banOwnIpForSeconds(15 * 60, detectedBrowserIp);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });
});
