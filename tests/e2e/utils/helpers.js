const notifier = require("node-notifier");
const path = require("path");
const fs = require('fs');
const { addDecision, deleteAllDecisions } = require("./watcherClient");
const {
    ADMIN_URL,
    BASE_URL,
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    DEBUG,
    TIMEOUT,
} = require("./constants");

const COOKIES_FILE_PATH = `${__dirname}/../.cookies.json`;

const notify = (message) => {
    if (DEBUG) {
        console.log(message);
        notifier.notify({
            title: "CrowdSec automation",
            message: message,
            icon: path.join(__dirname, "./icon.png"),
        });
    }
};

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
        "#adminmenuwrap > #adminmenu > #toplevel_page_crowdsec_plugin > .wp-has-submenu > .wp-menu-name"
    );
    await waitForNavigation;
};

const onAdminGoToAdvancedPage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(4) > a"
    );
    await waitForNavigation;

    const title = await page.title();
    await expect(title).toContain("Advanced");
};

const onAdminGoToThemePage = async () => {
    // CrowdSec Menu
    await page.hover("#toplevel_page_crowdsec_plugin");
    await page.click(
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(3) > a"
    );
    await waitForNavigation;

    const title = await page.title();
    await expect(title).toContain("Theme customization");
};

const onLoginPageLoginAsAdmin = async () => {
    await page.fill("#user_login", ADMIN_LOGIN);
    await page.fill("#user_pass", ADMIN_PASSWORD);
    await page.waitForSelector("#wp-submit");
    await page.click("#wp-submit");
    await waitForNavigation;
};

const onAdminSaveSettings = async () => {
    await page.click("[type=submit]");
    await waitForNavigation;

    await expect(page).toHaveText(
        "#setting-error-settings_updated",
        "Settings saved."
    );
    await wait(2000);
};

const setToggle = async (optionName, enable) => {
    const isEnabled = await page.$eval(
        `[name=${optionName}]`,
        (el) => el.checked
    );
    if (enable) {
        if (!isEnabled) {
            await page.click(`[for=${optionName}]`);
        }
    } else {
        if (isEnabled) {
            await page.click(`[for=${optionName}]`);
        }
    }
};

const onAdvancedPageEnableStreamMode = async () => {
    await setToggle("crowdsec_stream_mode", true);
};

const onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo = async (
    seconds
) => {
    await fillInput("crowdsec_clean_ip_cache_duration", seconds);
};

const onAdminAdvancedSettingsPageSetBadIpCacheDurationTo = async (seconds) => {
    await fillInput("crowdsec_bad_ip_cache_duration", seconds);
};

const computeCurrentPageRemediation = async (
    accessibleTextInTitle = "Just another WordPress site"
) => {
    const title = await page.title();
    if (title.includes(accessibleTextInTitle)) {
        return "bypass";
    } else {
        await expect(title).toContain("Oops");
        const description = await page.$eval('.desc', (el) => el.innerText);
        const banText =
            "cyber";
        const captchaText = "check";
        if (description.includes(banText)) {
            return "ban";
        } else if (description.includes(captchaText)) {
            return "captcha";
        }
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
        "This security check has been powered by"
    );
};

const publicHomepageShouldBeCaptchaWallWithMentions = async () => {
    await publicHomepageShouldBeCaptchaWall();
    await expect(page).toHaveText(
        ".main",
        "This security check has been powered by"
    );
};

const publicHomepageShouldBeAccessible = async () => {
    await goToPublicPage();
    const remediation = await computeCurrentPageRemediation();
    await expect(remediation).toBe("bypass");
};

const banIpForSeconds = async (ip, seconds) => {
    await addDecision(ip, "ban", seconds);
    await wait(1000);
};

const banOwnIpForSeconds = async (seconds, ip) => {
    await banIpForSeconds(ip, seconds);
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
};

const fillInput = async (optionName, value) => {
    await page.fill(`[name=${optionName}]`, "" + value);
};

const remediationShouldUpdate = async (
    accessibleTextInTitle,
    initialRemediation,
    newRemediation,
    timeoutMs,
    intervalMs = 1000
) =>
    new Promise((resolve, reject) => {
        let checkRemediationTimeout;
        let checkRemediationInterval;
        let initialPassed = false;
        const stopTimers = () => {
            if (checkRemediationInterval) {
                clearInterval(checkRemediationInterval);
            }
            if (checkRemediationTimeout) {
                clearTimeout(checkRemediationTimeout);
            }
        };

        checkRemediationInterval = setInterval(async () => {
            await page.reload();
            await waitForNavigation;
            const remediation = await computeCurrentPageRemediation(
                accessibleTextInTitle
            );
            if (remediation === newRemediation) {
                stopTimers();
                if (initialPassed) {
                    resolve();
                } else {
                    reject({
                        errorType: "INITIAL_REMEDIATION_NEVER_HAPPENED",
                        type: remediation,
                    });
                }
                return;
            } else if (remediation === initialRemediation) {
                initialPassed = true;
                return;
            } else {
                stopTimers();
                reject({
                    errorType: "WRONG_REMEDIATION_HAPPENED",
                    type: remediation,
                });
                return;
            }
        }, intervalMs);
        checkRemediationTimeout = setTimeout(() => {
            stopTimers();
            reject({ errorType: "NEW_REMEDIATION_NEVER_HAPPENED" });
            return;
        }, timeoutMs);
    });

const storeCookies = async () => {
    const cookies = await context.cookies();
    const cookieJson = JSON.stringify(cookies);
    fs.writeFileSync(COOKIES_FILE_PATH, cookieJson);
};

const loadCookies = async (context) => {
    const cookies = fs.readFileSync(COOKIES_FILE_PATH, "utf8");
    const deserializedCookies = JSON.parse(cookies);
    await context.addCookies(deserializedCookies);
};

module.exports = {
    notify,
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
    remediationShouldUpdate,
    storeCookies,
    loadCookies
};
