/* eslint-disable no-undef */
const {
    goToAdmin,
    onAdminGoToAdvancedPage,
    onAdminGoToSettingsPage,
    onAdminGoToThemePage,
    onAdminSaveSettings,
    onLoginPageLoginAsAdmin,
    setToggle,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    captchaOwnIpForSeconds,
    onCaptchaPageRefreshCaptchaImage,
    fillInput,
    setDefaultConfig,
    removeAllDecisions,
    selectByName,
    runCacheAction,
    publicHomepageShouldBeCaptchaWall,
    fillByName,
    wait,
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Run in Live mode`, () => {
    beforeAll(async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await setDefaultConfig();
    });

    it('Should display the homepage with no remediation"', async () => {
        await publicHomepageShouldBeAccessible();
    });

    it('Should display a captcha wall"', async () => {
        await captchaOwnIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        // Refresh the captcha 2 times
        await onCaptchaPageRefreshCaptchaImage();
        await onCaptchaPageRefreshCaptchaImage();

        // Disable CrowdSec Mentions
        await goToAdmin();
        // await onLoginPageLoginAsAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_hide_mentions", true);
        await onAdminSaveSettings();
        await wait(1000);
        await publicHomepageShouldBeCaptchaWallWithoutMentions();

        // Play with colors and texts
        await goToAdmin();
        await onAdminGoToThemePage();
        await fillInput("crowdsec_theme_color_text_primary", "white");
        await fillInput("crowdsec_theme_color_text_secondary", "#333");
        await fillInput("crowdsec_theme_color_text_button", "white");
        await fillInput("crowdsec_theme_color_text_error_message", "red");
        await fillInput("crowdsec_theme_color_background_page", "black");
        await fillInput("crowdsec_theme_color_background_container", "#1f2135");
        await fillInput("crowdsec_theme_color_background_button", "#103ea5");
        await fillInput(
            "crowdsec_theme_color_background_button_hover",
            "#2858c3",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_tab_title",
            "Oops alors!",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_title",
            "Ah! désolé mais...",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_subtitle",
            "Merci de compléter ce petit check de sécurité pour continuer..",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_refresh_image_link",
            "Rafraîchir l'image",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_captcha_placeholder",
            "Taper ici...",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_send_button",
            "Continuer",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_error_message",
            "Merci de réessayer.",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_footer",
            "A très vite.",
        );

        await fillInput("crowdsec_theme_text_ban_wall_tab_title", "Oops!!");
        await fillInput("crowdsec_theme_text_ban_wall_title", "🤭 Ohoh..");
        await fillInput(
            "crowdsec_theme_text_ban_wall_subtitle",
            "Cette page est protégée contre les cyber-attaques et votre IP a été bannie par notre système.",
        );
        await fillInput(
            "crowdsec_theme_text_captcha_wall_footer",
            "En esperant vous revoir rétabli.",
        );

        await fillInput(
            "crowdsec_theme_custom_css",
            "body {background: rgb(2,0,36);background: linear-gradient(90deg, rgba(2,0,36,1) 0%, rgba(2,10,37,1) 35%, rgba(0,96,116,1) 100%);}",
        );
        await onAdminSaveSettings();

        // Re enable settings
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_hide_mentions", false);
        await onAdminSaveSettings();
    });

    it("Should refresh image", async () => {
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const phrase = await page.$eval("h1", (el) => el.innerText);
        await publicHomepageShouldBeCaptchaWall();
        await page.click("#refresh_link");
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const newPhrase = await page.$eval("h1", (el) => el.innerText);
        await expect(newPhrase).not.toEqual(phrase);
    });

    it("Should show error message", async () => {
        await publicHomepageShouldBeCaptchaWall();
        expect(await page.locator(".error").count()).toBeFalsy();
        await fillByName("phrase", "bad-value");
        await page.locator('button:text("CONTINUE")').click();
        expect(await page.locator(".error").count()).toBeTruthy();
    });

    it("Should solve the captcha", async () => {
        await runCacheAction("captcha-phrase", `&ip=${CURRENT_IP}`);
        const phrase = await page.$eval("h1", (el) => el.innerText);
        await publicHomepageShouldBeCaptchaWall();
        await fillByName("phrase", phrase);
        await page.locator('button:text("CONTINUE")').click();
        await publicHomepageShouldBeAccessible();
        // Clear cache for next tests
        await runCacheAction("clear");
    });

    it('Should display a ban wall"', async () => {
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });

    it('Should bypass is bouncing disabled"', async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await selectByName("crowdsec_bouncing_level", "bouncing_disabled");
        await onAdminSaveSettings();
        await publicHomepageShouldBeAccessible();
    });

    it('Should enable cURL"', async () => {
        await removeAllDecisions();
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await selectByName("crowdsec_bouncing_level", "normal_bouncing");
        await setToggle("crowdsec_use_curl", true);
        await onAdminSaveSettings();
    });

    it('Should display the homepage with no remediation"', async () => {
        await publicHomepageShouldBeAccessible();
    });

    it('Should display a captcha wall"', async () => {
        await captchaOwnIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        // Disable CrowdSec Mentions
        await goToAdmin();
        // await onLoginPageLoginAsAdmin();
        await onAdminGoToAdvancedPage();
        await setToggle("crowdsec_hide_mentions", true);
        await onAdminSaveSettings();
        await publicHomepageShouldBeCaptchaWallWithoutMentions();
    });

    it('Should display a ban wall"', async () => {
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeBanWall();
    });
});
