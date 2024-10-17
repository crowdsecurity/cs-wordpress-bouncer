const {
    removeAllDecisions,
    runCacheAction,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeBanWall,
    fillByName,
    publicHomepageShouldBeAccessible,
    goToAdmin,
    onLoginPageLoginAsAdmin,
    setDefaultConfig,
    enableAppSec,
    onAdminGoToSettingsPage,
    onAdminGoToAdvancedPage,
    selectByName,
    onAdminSaveSettings,
} = require("../utils/helpers");
const { CURRENT_IP } = require("../utils/constants");

describe(`Should be captcha by AppSec because of timeout`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await runCacheAction("clear");
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
        await enableAppSec();
    });

    it("Should captcha for home page as this is the appsec fallback remediation", async () => {
        await publicHomepageShouldBeCaptchaWall();
    });

    it("Should solve the captcha", async () => {
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        // eslint-disable-next-line no-undef
        const phrase = await page.$eval("h1", (el) => el.innerText);
        await publicHomepageShouldBeCaptchaWall();
        await fillByName("phrase", phrase);
        // eslint-disable-next-line no-undef
        await page.locator('button:text("CONTINUE")').click();
        await publicHomepageShouldBeAccessible();
        // Clear cache for next tests
        await removeAllDecisions();
        await runCacheAction("clear");
    });

    it("Should ban for home page as this is the appsec fallback remediation", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await onAdminGoToAdvancedPage();
        await selectByName("crowdsec_appsec_fallback_remediation", "ban");
        await onAdminSaveSettings();
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await runCacheAction("clear");
    });

    it("Should bypass for home page as this is the appsec fallback remediation", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await onAdminGoToAdvancedPage();
        await selectByName("crowdsec_appsec_fallback_remediation", "bypass");
        await onAdminSaveSettings();
        await publicHomepageShouldBeAccessible();
    });
});
