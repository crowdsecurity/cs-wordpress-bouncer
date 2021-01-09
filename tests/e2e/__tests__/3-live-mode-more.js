const { CLIENT_IP, OTHER_IP } = require("../utils/constants");
const {
    notify,
    addDecision,
    wait,
    goToAdmin,
    onAdminGoToSettingsPage,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    removeAllDecisions,
    fillInput,
    loadCookies,
} = require("../utils/helpers");

describe(`Run in Live mode`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    beforeAll(() => loadCookies(context));

    it('Should display a captcha wall instead of a ban wall in Flex mode"', async () => {
        // set Flex mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "flex_boucing"
        );
        await onAdminSaveSettings();

        // Should be a captcha wall
        await publicHomepageShouldBeCaptchaWall();
    });

    it('Should be accessible in Disabled mode"', async () => {
        // set Disabled mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "bouncing_disabled"
        );
        await onAdminSaveSettings();

        // Should be accessible
        await publicHomepageShouldBeAccessible();

        // Go back to normal mode
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await page.selectOption(
            "[name=crowdsec_bouncing_level]",
            "normal_boucing"
        );
        await onAdminSaveSettings();

        // Should be a ban wall
        await publicHomepageShouldBeBanWall();
    });

    it('Should display back the homepage with no remediation"', async () => {
        await removeAllDecisions();
        await publicHomepageShouldBeAccessible();
    });

    it("Should fallback to the selected remediation for unknown remediation", async () => {
        await removeAllDecisions();
        await addDecision(CLIENT_IP, "mfa", 15 * 60);
        await wait(1000);
        await publicHomepageShouldBeCaptchaWall();
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.selectOption(
            "[name=crowdsec_fallback_remediation]",
            "bypass"
        );
        await onAdminSaveSettings();
        await publicHomepageShouldBeAccessible();
    });

    it('Should handle X-Forwarded-For header for whitelisted IPs only"', async () => {
        await removeAllDecisions();
        await banIpForSeconds(OTHER_IP, 15 * 60);

        // Should be banned as current IP is not trust by CDN
        page.setExtraHTTPHeaders({ "X-Forwarded-For": OTHER_IP });
        await publicHomepageShouldBeAccessible();

        // Add the current IP to the CDN list (via a range)
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_trust_ip_forward_list", CLIENT_IP + "/30");
        await onAdminSaveSettings();

        // Should be banned
        await publicHomepageShouldBeBanWall();

        // Remove the XFF header for next requests
        page.setExtraHTTPHeaders({});
    });
});
