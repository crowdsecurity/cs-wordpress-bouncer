const notifier = require("node-notifier");
const path = require("path");

const BASE_URL = "http://localhost";
const BOUNCER_KEY = process.env.BOUNCER_KEY;
const CLIENT_IP = process.env.CS_WP_HOST;
const WORDPRESS_VERSION = process.env.WORDPRESS_VERSION;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "my_very_very_secret_admin_password";
const LAPI_URL = process.env.LAPI_URL_FROM_CONTAINERS;
const DEBUG = !!process.env.DEBUG;
const TIMEOUT = (!!process.env.DEBUG ? 5 * 60 : 8) * 1000;
const OTHER_IP = "1.2.3.4";
const adminUrl = `${BASE_URL}/wp-admin/`;

const notify = (message) => {
    if (DEBUG) {
        notifier.notify({
            title: "CrowdSec automation",
            message: message,
            icon: path.join(__dirname, "../icon.png"),
        });
    }
};

const { addDecision, deleteAllDecisions } = require("../utils/watcherClient");

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

jest.setTimeout(TIMEOUT);

const WP56 = WORDPRESS_VERSION === "";
const WP55 = WORDPRESS_VERSION === "5.5";
const WP54 = WORDPRESS_VERSION === "5.4";
const WP53 = WORDPRESS_VERSION === "5.3";

const waitForNavigation = page.waitForNavigation();

const goToAdmin = async () => {
    await page.goto(adminUrl);
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
        "#toplevel_page_crowdsec_plugin > ul > li:nth-child(3) > a"
    );
    await waitForNavigation;

    const title = await page.title();
    await expect(title).toContain("Advanced");
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
        await expect(title).toContain("Oops..");
        const description = await page.$eval(`.desc`, (el) => el.innerText);
        const banText =
            "This page is protected against cyber attacks and your IP has been banned by our system.";
        const captchaText = "Please complete the security check.";
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

const banOwnIpForSeconds = async (seconds) => {
    await banIpForSeconds(CLIENT_IP, seconds);
};

const captchaOwnIpForSeconds = async (seconds) => {
    await addDecision(CLIENT_IP, "captcha", seconds);
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

    describe(`Setup WordPress ${WORDPRESS_VERSION} and CrowdSec plugin`, () => {

    beforeEach(() => (DEBUG ? console.log(expect.getState().currentTestName) : null))

    it('Should install wordpress"', async () => {
        notify(`Setup WordPress ${WORDPRESS_VERSION} and CrowdSec plugin`);

        // Go to home
        await goToPublicPage();

        if (WP54 || WP55 || WP56) {
            // "Language selection" page
            await page.click('option[lang="en"]');
            await page.click("#language-continue");
            await waitForNavigation;
        }

        // "Account creation" page
        await page.fill("#weblog_title", "My website");
        await page.fill("#user_login", ADMIN_LOGIN);
        if (WP53 || WP54 || WP55 || WP56) {
            await page.fill("#pass1", ADMIN_PASSWORD);
        } else {
            await page.fill("#pass1-text", ADMIN_PASSWORD);
        }
        await page.fill("#admin_email", "admin@admin.admin");
        await page.click("#submit");
        await waitForNavigation;

        // "Success" page

        await expect(page).toHaveText("h1", "Success!");
        await page.click(".wp-core-ui > .step > .button");
        await waitForNavigation;
    });

    it('Should login to wp-admin"', async () => {
        // "Login" page
        await onLoginPageLoginAsAdmin();
    });

    it('Should install CrowdSec plugin"', async () => {
        // "Plugins" page
        await page.goto(`${adminUrl}/plugins.php'`);
        if (WP55 || WP56) {
            await page.click("#activate-crowdsec");
        } else {
            await page.click('[aria-label="Activate CrowdSec"]');
        }

        await waitForNavigation;
        await expect(page).toHaveText("#message", "Plugin activated.");
    });

    it('Should configure the connection details"', async () => {
        await onAdminGoToSettingsPage();
        await fillInput("crowdsec_api_url", LAPI_URL);
        await fillInput("crowdsec_api_key", BOUNCER_KEY);
        await onAdminSaveSettings();
    });

    it('Should reduce the live mode cache durations"', async () => {
        await onAdminGoToAdvancedPage();
        await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(1);
        await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(1);
        await onAdminSaveSettings();
    });

    it('Should reduce stream mode refresh frequency"', async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_stream_mode_refresh_frequency", 1);
        await onAdminSaveSettings();
    });


});

describe(`Run in Live mode`, () => {

    beforeEach(() => (DEBUG ? console.log(expect.getState().currentTestName) : null))

    it('Should display the homepage with no remediation"', async () => {
        notify("Run in Live mode");
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

    it("Should prune the File system cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_prune_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec cache has just been pruned."
        );
    });

    it("Should clear the cache on demand", async () => {
        await onAdminGoToAdvancedPage();
        await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(60);
        await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(60);
        await onAdminSaveSettings();
        await banOwnIpForSeconds(15 * 60);
        await publicHomepageShouldBeBanWall();
        wait(2000);
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        wait(2000);
        await publicHomepageShouldBeBanWall();

        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.on("dialog", async (dialog) => {
            await dialog.accept();
        });
        await page.click("#crowdsec_clear_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "CrowdSec cache has just been cleared."
        );
        await publicHomepageShouldBeAccessible();
    });
});

describe(`Run in Stream mode`, () => {

    beforeEach(() => (DEBUG ? console.log(expect.getState().currentTestName) : null))

    it('Should enable the stream mode"', async () => {
        notify("Run in Stream mode");
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await onAdvancedPageEnableStreamMode();
        await onAdminSaveSettings();
    });

    it('Should display a ban wall via stream mode"', async () => {
        await banOwnIpForSeconds(15 * 60);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
    });

    it('Should display back the homepage with no remediation via stream mode"', async () => {
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });

    it("Should refresh the cache", async () => {
        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.click("#crowdsec_refresh_cache");
        await waitForNavigation;

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "The cache has just been refreshed (0 new decision, 0 deleted)."
        );
    });
});

describe(`Use Redis technology`, () => {

    beforeEach(() => (DEBUG ? console.log(expect.getState().currentTestName) : null))

    it('Should be able to use Redis cache"', async () => {
        notify("Use Redis technology");

        // TODO (+ bad DSN format, + DSN down)

        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.selectOption("[name=crowdsec_cache_system]", "redis");
        await wait(200);
        await fillInput("crowdsec_redis_dsn", "redis://redis:6379"); // TODO test bad DSN format and test DSN down
        await onAdminSaveSettings();

        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "As the stream mode is enabled, the cache has just been warmed up, there is now 0 decision in cache."
        );

        await publicHomepageShouldBeAccessible();
        await banOwnIpForSeconds(15 * 60);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });
});

describe(`Use Memcached technology`, () => {

    beforeEach(() => (DEBUG ? console.log(expect.getState().currentTestName) : null))

    it('Should be able to use Memcached cache"', async () => {
        notify("Use Memcached technology");

        await goToAdmin();
        await onAdminGoToAdvancedPage();
        await page.selectOption("[name=crowdsec_cache_system]", "memcached");
        await wait(200);
        await fillInput(
            "crowdsec_memcached_dsn",
            "memcached://memcached:11211"
        );

        // TODO test bad DSN format and test DSN down

        await onAdminSaveSettings();
        await expect(page).toHaveText(
            "#wpbody-content > div.wrap > div.notice.notice-success",
            "As the stream mode is enabled, the cache has just been warmed up, there is now 0 decision in cache."
        );

        await publicHomepageShouldBeAccessible();
        await banOwnIpForSeconds(15 * 60);
        await forceCronRun();
        await publicHomepageShouldBeBanWall();
        await removeAllDecisions();
        await forceCronRun();
        await publicHomepageShouldBeAccessible();
    });
});

/*

# Stream mode: Resync decisions each

Remove all decisions + Ban current IP during 15min
Set stream mode with 15 seconds resync
Refresh cache
(to finish writing)

# Recheck clean IP (to write)

# Recheck Bad IP (to write)

*/
