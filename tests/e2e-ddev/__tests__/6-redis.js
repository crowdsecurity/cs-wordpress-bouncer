/* eslint-disable no-undef */
const {
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
    onLoginPageLoginAsAdmin,
    setDefaultConfig,
    onAdvancedPageEnableStreamMode,
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Use Redis technology`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
    });

    it('Should be able to use Redis cache"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableStreamMode();
        await page.selectOption("[name=crowdsec_cache_system]", "redis");
        await wait(200);
        await fillInput("crowdsec_redis_dsn", "redis://redis:6379");
        await onAdminSaveSettings();
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "As the stream mode is enabled, the cache has just been refreshed.",
        );

        await publicHomepageShouldBeAccessible();
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
        // Bad dsn
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_redis_dsn", "redis://redis:1234");
        await onAdminSaveSettings(false);
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-error",
            "There was an error while testing new DSN",
        );
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-error",
            "Rollback to old DSN: redis://redis:6379",
        );
    });
});
