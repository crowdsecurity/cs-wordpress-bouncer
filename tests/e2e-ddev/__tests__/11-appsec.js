const {
    goToPublicPage,
    removeAllDecisions,
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminSaveSettings,
    computeCurrentPageRemediation,
    publicHomepageShouldBeAccessible,
    clickById,
    selectByName,
    onLoginPageLoginAsAdmin,
    enableAppSec,
    getTextById,
    fillById,
    captchaOwnIpForSeconds,
    setDefaultConfig,
    runCacheAction,
    wait,
    getHtmlById,
    fillByName,
} = require("../utils/helpers");
const {
    APPSEC_TEST_URL,
    APPSEC_WP_PAGE,
    APPSEC_UPLOAD_WP_PAGE,
    APPSEC_MALICIOUS_BODY,
    CURRENT_IP,
} = require("../utils/constants");

// With default config, the body is limited to 100KB
const maxBodyString = "a".repeat(100 * 1024);

describe(`Should work with AppSec`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
        await enableAppSec();
    });

    it("Should bypass for home page GET", async () => {
        await runCacheAction("clear");
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

    it("Should send header only if body is too long", async () => {
        await goToPublicPage(APPSEC_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec – WordPress",
        );
        await expect(remediation).toBe("bypass");

        let appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("INITIAL STATE");

        await fillById("request-body", APPSEC_MALICIOUS_BODY + maxBodyString);
        await clickById("appsec-post-button");
        await wait(1000);

        appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("Response status: 200");
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

    it("Should ban if body size exceeds limit", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await selectByName(
            "crowdsec_appsec_body_size_exceeded_action",
            "block",
        );
        await onAdminSaveSettings();
        await goToPublicPage(APPSEC_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec – WordPress",
        );
        await expect(remediation).toBe("bypass");

        let appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("INITIAL STATE");

        await fillById("request-body", `${maxBodyString}OK`);
        await clickById("appsec-post-button");
        await wait(1000);

        appsecResult = await getTextById("appsec-result");
        await expect(appsecResult).toBe("Response status: 403");
    });

    it("Should NOT ban if body size NOT exceeds limit with upload", async () => {
        await goToPublicPage(APPSEC_UPLOAD_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec Upload – WordPress",
        );
        await expect(remediation).toBe("bypass");

        await expect(await page.getByAltText("Uploaded Image").count()).toEqual(
            0,
        );

        await page
            .locator('input[name="file"]')
            .setInputFiles("./utils/icon.png");
        await page.getByRole("button", { name: "Upload Image" }).click();
        await wait(2000);
        await expect(await page.getByAltText("Uploaded Image").count()).toEqual(
            1,
        );
    });

    it("Should ban if body size exceeds limit with upload", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        // Tested file is about 55KB
        await fillByName("crowdsec_appsec_max_body_size_kb", 50);
        await onAdminSaveSettings();

        await goToPublicPage(APPSEC_UPLOAD_WP_PAGE);
        const remediation = await computeCurrentPageRemediation(
            "AppSec Upload – WordPress",
        );
        await expect(remediation).toBe("bypass");

        await expect(await page.getByAltText("Uploaded Image").count()).toEqual(
            0,
        );

        await page
            .locator('input[name="file"]')
            .setInputFiles("./utils/icon.png");
        await page.getByRole("button", { name: "Upload Image" }).click();
        await wait(2000);
        await expect(await page.getByAltText("Uploaded Image").count()).toEqual(
            0,
        );

        const appsecResult = await getHtmlById("uploadedImage");
        await expect(appsecResult).toBe("403");
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
