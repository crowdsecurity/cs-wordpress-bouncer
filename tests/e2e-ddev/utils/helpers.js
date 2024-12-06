/* eslint-disable no-undef */
const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    BASE_URL,
    BASE_ADMIN_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    BOUNCER_KEY,
    LAPI_URL_FROM_WP,
    TIMEOUT,
    PROXY_IP,
    MULTISITE,
    APPSEC_URL,
} = require("./constants");

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const goToAdmin = async (endpoint = "") => {
    const adminUrl =
        MULTISITE == "true" ? `${ADMIN_URL}network/` : `${ADMIN_URL}`;
    await page.goto(`${adminUrl}${endpoint}`);
};

const goToPublicPage = async (endpoint = "") => {
    await page.goto(`${BASE_URL}${endpoint}`);
};

const runCacheAction = async (actionType = "refresh", otherParams = "") => {
    await goToPublicPage(
        `/cache-actions.php?action=${actionType}${otherParams}`,
    );
    await page.waitForLoadState("networkidle");
    await expect(page).not.toMatchTitle(/404/);
    await expect(page).toMatchTitle(`Cache action: ${actionType}`);
};

const onAdminGoToSettingsPage = async () => {
    if (MULTISITE == "true") {
        await goToPublicPage("/wp-admin/network/");
    }
    // CrowdSec Menu
    await page.click(
        "#adminmenuwrap > #adminmenu > #toplevel_page_crowdsec_plugin > .wp-has-submenu > .wp-menu-name",
    );
};

const onAdminGoToAdvancedPage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(4) > a",
    );
    await wait(1000);
    await expect(page).toMatchTitle(/Advanced/);
};

const onAdminGoToThemePage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(3) > a",
    );
    await wait(1000);

    await expect(page).toMatchTitle(/Theme customization/);
};

const onLoginPageLoginAsAdmin = async () => {
    await wait(2000);
    await page.fill("#user_login", ADMIN_LOGIN);
    await wait(2000);
    await page.fill("#user_pass", ADMIN_PASSWORD);
    await wait(2000);
    await page.waitForSelector("#wp-submit");
    await page.click("#wp-submit");
};

const onAdminSaveSettings = async (check = true) => {
    await page.click("[type=submit]");

    if (check) {
        if (MULTISITE == "true") {
            await expect(page).toHaveText(".wrap", "saved.");
        } else {
            await expect(page).toHaveText(
                "#setting-error-settings_updated",
                "Settings saved.",
            );
        }
    }

    await wait(2500);
};

const selectElement = async (selectId, valueToSelect) => {
    await page.selectOption(`[id=${selectId}]`, `${valueToSelect}`);
};

const selectByName = async (selectName, valueToSelect) => {
    await page.selectOption(`[name=${selectName}]`, `${valueToSelect}`);
};

const setToggle = async (optionName, enable) => {
    await page.waitForSelector(`[name=${optionName}]`, { state: "attached" });
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

const fillByName = async (name, value) => {
    await page.fill(`[name=${name}]`, `${value}`);
};

const fillById = async (name, value) => {
    await page.fill(`[id=${name}]`, `${value}`);
};

const clickById = async (id) => {
    await page.click(`#${id}`);
};

const getTextById = async (id) => {
    return page.locator(`#${id}`).innerText();
};

const getHtmlById = async (id) => {
    return page.locator(`#${id}`).innerHTML();
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
    accessibleTextInTitle = "WordPress",
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
};

const forceCronRun = async () => {
    // force WP Cron to run cache update as bouncing is done before cache updating
    // This could be fixed by running homemade call to cache update
    // if it's the time to update cache
    await page.goto(`${BASE_ADMIN_URL}/wp-cron.php`);
    await wait(2000);
};

const setDefaultConfig = async () => {
    await onAdminGoToSettingsPage();
    await fillInput("crowdsec_api_url", LAPI_URL_FROM_WP);
    await selectByName("crowdsec_auth_type", "api_key");
    await fillInput("crowdsec_api_key", BOUNCER_KEY);
    await setToggle("crowdsec_use_curl", false);
    await setToggle("crowdsec_api_timeout", 120);
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

    await fillInput("crowdsec_trust_ip_forward_list", PROXY_IP);
    await selectByName("crowdsec_fallback_remediation", "captcha");

    // Geolocation
    await setToggle("crowdsec_geolocation_enabled", false);

    // AppSec
    await setToggle("crowdsec_use_appsec", false);

    // Tests
    await fillInput("crowdsec_forced_test_ip", "");
    await fillInput("crowdsec_forced_test_forwarded_ip", "");

    // Do not set auto_prepend_file mode to false as it will make auto_prepend_file tests fail

    await onAdminSaveSettings();
};

const enableAutoPrependFileMode = async () => {
    await onAdminGoToSettingsPage();
    await onAdminGoToAdvancedPage();
    await setToggle("crowdsec_auto_prepend_file_mode", true);
    await onAdminSaveSettings();
};

const disableAutoPrependFileMode = async () => {
    await onAdminGoToSettingsPage();
    await onAdminGoToAdvancedPage();
    await setToggle("crowdsec_auto_prepend_file_mode", false);
    await onAdminSaveSettings();
};

const enableAppSec = async () => {
    await onAdminGoToSettingsPage();
    await onAdminGoToAdvancedPage();
    await setToggle("crowdsec_use_appsec", true);
    await fillInput("crowdsec_appsec_url", APPSEC_URL);
    await selectByName("crowdsec_appsec_fallback_remediation", "captcha");
    await selectByName(
        "crowdsec_appsec_body_size_exceeded_action",
        "headers_only",
    );
    await fillInput("crowdsec_appsec_max_body_size_kb", 100);
    await onAdminSaveSettings();
};

module.exports = {
    addDecision,
    wait,
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
    fillById,
    setDefaultConfig,
    selectElement,
    selectByName,
    runCacheAction,
    fillByName,
    enableAutoPrependFileMode,
    enableAppSec,
    clickById,
    getTextById,
    computeCurrentPageRemediation,
    disableAutoPrependFileMode,
    getHtmlById,
};
