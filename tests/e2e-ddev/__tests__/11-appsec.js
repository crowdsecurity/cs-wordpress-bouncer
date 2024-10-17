const {
    goToPublicPage,
    removeAllDecisions,
    goToAdmin,
    computeCurrentPageRemediation,
    publicHomepageShouldBeAccessible,
    fillInput,
    clickById,
    setDefaultConfig,
    onLoginPageLoginAsAdmin,
    enableAppSec,
    getTextById,
    fillById,
    captchaOwnIpForSeconds,
    wait,
} = require("../utils/helpers");
const {
    APPSEC_TEST_URL,
    APPSEC_WP_PAGE,
    APPSEC_MALICIOUS_BODY,
    CURRENT_IP,
} = require("../utils/constants");

describe(`Should work with AppSec`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
        await enableAppSec();
    });

    it("Should bypass for home page GET", async () => {
        await publicHomepageShouldBeAccessible();
    });

    it("Should ban when access AppSec test page with GET", async () => {
        await goToPublicPage(APPSEC_TEST_URL);
        const remediation = await computeCurrentPageRemediation();
        await expect(remediation).toBe("ban");
    });

    it("Should ban when access with POST and malicious body", async () => {
        await goToPublicPage(APPSEC_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec – WordPress",
        );
        await expect(remediation).toBe("bypass");

        let appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("INITIAL STATE");

        await fillById("request-body", APPSEC_MALICIOUS_BODY);
        await clickById("appsec-post-button");
        await wait(1000);

        appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("Response status: 403");
    });

    it("Should bypass when access with POST and clean body", async () => {
        await goToPublicPage(APPSEC_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec – WordPress",
        );
        await expect(remediation).toBe("bypass");

        let appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("INITIAL STATE");

        await fillById("request-body", "OK");
        await clickById("appsec-post-button");
        await wait(1000);

        appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("Response status: 200");
    });

    it("Should not use AppSec if LAPI remediation is not a bypass", async () => {
        await goToPublicPage(APPSEC_TEST_URL);
        let remediation = await computeCurrentPageRemediation(
            "AppSec - WordPress",
        );
        await expect(remediation).toBe("ban");

        await captchaOwnIpForSeconds(15 * 60, CURRENT_IP);
        // Wait because clean ip cache duration
        await wait(2000);
        await goToPublicPage(APPSEC_TEST_URL);
        remediation = await computeCurrentPageRemediation();
        await expect(remediation).toBe("captcha");
    });
});
