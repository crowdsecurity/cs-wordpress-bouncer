/* eslint-disable no-undef */
const fs = require("fs");
const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    BASE_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    BOUNCER_KEY,
    LAPI_URL_FROM_WP,
    TIMEOUT,
    PROXY_IP,
} = require("./constants");

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const waitForNavigation = page.waitForNavigation();

const goToAdmin = async () => {
    await page.goto(ADMIN_URL);
    await waitForNavigation;
};

const goToPublicPage = async () => {
    await page.goto(`${BASE_URL}`);
    await waitForNavigation;
};

const onAdminGoToSettingsPage = async () => {
    // CrowdSec Menu
    await page.click(
        "#adminmenuwrap > #adminmenu > #toplevel_page_crowdsec_plugin > .wp-has-submenu > .wp-menu-name",
    );
    await waitForNavigation;
};

const onAdminGoToAdvancedPage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(4) > a",
    );
    await waitForNavigation;
    await wait(1000);
    await expect(page).toMatchTitle(/Advanced/);
};

const onAdminGoToThemePage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(3) > a",
    );
    await waitForNavigation;
    await wait(1000);

    await expect(page).toMatchTitle(/Theme customization/);
};

const onLoginPageLoginAsAdmin = async () => {
    await page.fill("#user_login", ADMIN_LOGIN);
    await page.fill("#user_pass", ADMIN_PASSWORD);
    await page.waitForSelector("#wp-submit");
    await page.click("#wp-submit");
    await waitForNavigation;
};

const onAdminSaveSettings = async (check = true) => {
    await page.click("[type=submit]");
    await waitForNavigation;

    if (check) {
        await expect(page).toHaveText(
            "#setting-error-settings_updated",
            "Settings saved.",
        );
    }

    await wait(2000);
};

const selectElement = async (selectId, valueToSelect) => {
    await page.selectOption(`[id=${selectId}]`, `${valueToSelect}`);
};

const selectByName = async (selectName, valueToSelect) => {
    await page.selectOption(`[name=${selectName}]`, `${valueToSelect}`);
};

const setToggle = async (optionName, enable) => {
    await page.waitForSelector(`[name=${optionName}]`, {state: "attached"});
    const isEnabled = await page.$eval(
        `[name=${optionName}]`,
        (el) => el.checked,
    );
    if (enable) {
        if (!isEnabled) {
            await page.click(`[for=${optionName}]`);
        }
    } else if (isEnabled) {
        await page.click(`[for=${optionName}]`);
    }
};

const fillInput = async (optionName, value) => {
    await page.fill(`[name=${optionName}]`, `${value}`);
};

const onAdvancedPageEnableStreamMode = async () => {
    await setToggle("crowdsec_stream_mode", true);
    await fillInput("crowdsec_stream_mode_refresh_frequency", 1);
};

const onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo = async (
    seconds,
) => {
    await fillInput("crowdsec_clean_ip_cache_duration", seconds);
};

const onAdminAdvancedSettingsPageSetBadIpCacheDurationTo = async (seconds) => {
    await fillInput("crowdsec_bad_ip_cache_duration", seconds);
};

const computeCurrentPageRemediation = async (
    accessibleTextInTitle = "Just another WordPress site",
) => {
    const title = await page.title();
    if (title.includes(accessibleTextInTitle)) {
        return "bypass";
    }
    await expect(page).toMatchTitle(/Oops/);
    await page.waitForSelector(".desc");
    const description = await page.$eval(".desc", (el) => el.innerText);
    const banText = "cyber";
    const captchaText = "check";
    if (description.includes(banText)) {
        return "ban";
    }
    if (description.includes(captchaText)) {
        return "captcha";
    }

    throw Error("Current remediation can not be computed");
};

const publicHomepageShouldBeBanWall = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("ban");
};

const publicHomepageShouldBeCaptchaWall = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("captcha");
};

const publicHomepageShouldBeCaptchaWallWithoutMentions = async () => {
    await publicHomepageShouldBeCaptchaWall();
    await expect(page).not.toHaveText(
        ".main",
        "This security check has been powered by",
    );
};

const publicHomepageShouldBeCaptchaWallWithMentions = async () => {
    await publicHomepageShouldBeCaptchaWall();
    await expect(page).toHaveText(
        ".main",
        "This security check has been powered by",
    );
};

const publicHomepageShouldBeAccessible = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("bypass");
};

const banIpForSeconds = async (ip, seconds) => {
    await addDecision(ip, "ban", seconds);
    await wait(2000);
};

const banOwnIpForSeconds = async (seconds, ip) => {
    await banIpForSeconds(ip, seconds);
    await wait(1000);
};

const captchaOwnIpForSeconds = async (seconds, ip) => {
    await addDecision(ip, "captcha", seconds);
    await wait(1000);
};

const removeAllDecisions = async () => {
    await deleteAllDecisions();
    await wait(1000);
};

const onCaptchaPageRefreshCaptchaImage = async () => {
    await page.click("#refresh_link");
    await waitForNavigation;
};

const forceCronRun = async () => {
    // force WP Cron to run cache update as bouncing is done before cache updating
    // This could be fixed by running homemade call to cache update
    // if it's the time to update cache
    await page.goto(`${BASE_URL}/wp-cron.php`);
    await wait(2000);
};


const setDefaultConfig = async () => {
    await onAdminGoToSettingsPage();
    await fillInput("crowdsec_api_url", LAPI_URL_FROM_WP);
    await fillInput("crowdsec_auth_type", "api_key");
    await fillInput("crowdsec_api_key", BOUNCER_KEY);
    await setToggle("crowdsec_use_curl", false);
    await selectByName("crowdsec_bouncing_level", "normal_bouncing");
    await onAdminSaveSettings(false);

    await onAdminGoToAdvancedPage();
    await setToggle("crowdsec_debug_mode", true);
    await setToggle("crowdsec_disable_prod_log", false);
    await setToggle("crowdsec_display_errors", true);
    await setToggle("crowdsec_hide_mentions", false);
    await selectByName("crowdsec_cache_system", "phpfs");
    await setToggle("crowdsec_stream_mode", false);
    // We have to save in order that cache duration fields to be visible (not disabled)
    await onAdminSaveSettings(false);
    await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(1);
    await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(1);
    await fillInput("crowdsec_stream_mode_refresh_frequency", 1);

    await fillInput("crowdsec_trust_ip_forward_list", PROXY_IP);
    await selectByName("crowdsec_fallback_remediation", "captcha");

    // Geolocation
    await setToggle("crowdsec_geolocation_enabled", false);

    // Tests
    await fillInput("crowdsec_forced_test_ip", "");
    await fillInput("crowdsec_forced_test_forwarded_ip", "");

    await onAdminSaveSettings();
};

module.exports = {
    addDecision,
    wait,
    waitForNavigation,
    goToAdmin,
    goToPublicPage,
    onAdminGoToSettingsPage,
    onAdminGoToAdvancedPage,
    onAdminGoToThemePage,
    onAdminSaveSettings,
    setToggle,
    onLoginPageLoginAsAdmin,
    onAdvancedPageEnableStreamMode,
    onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo,
    onAdminAdvancedSettingsPageSetBadIpCacheDurationTo,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWall,
    publicHomepageShouldBeCaptchaWallWithoutMentions,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    banIpForSeconds,
    banOwnIpForSeconds,
    captchaOwnIpForSeconds,
    removeAllDecisions,
    onCaptchaPageRefreshCaptchaImage,
    forceCronRun,
    fillInput,
    setDefaultConfig,
    selectElement,
    selectByName,
};
