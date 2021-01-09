const {
    notify,
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    setToggle,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    captchaOwnIpForSeconds,
    onCaptchaPageRefreshCaptchaImage,
    loadCookies,
} = require("../utils/helpers");

describe(`Run in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(() => loadCookies(context));

    it('Should display the homepage with no remediation"', async () => {
        await publicHomepageShouldBeAccessible();
    });

    it('Should display a captcha wall"', async () => {
        await captchaOwnIpForSeconds(15 * 60);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        // Refresh the captcha 2 times
        await onCaptchaPageRefreshCaptchaImage();
        await onCaptchaPageRefreshCaptchaImage();

        // Disable CrowdSec Mentions
        await goToAdmin();
        //await onLoginPageLoginAsAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_hide_mentions", true);
        await onAdminSaveSettings();
        await publicHomepageShouldBeCaptchaWallWithoutMentions();

        // Re enable settings
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_hide_mentions", false);
        await onAdminSaveSettings();
    });

    it('Should display a ban wall"', async () => {
        await banOwnIpForSeconds(15 * 60);
        await publicHomepageShouldBeBanWall();
    });
});
