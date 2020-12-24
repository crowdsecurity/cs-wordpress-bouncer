const notifier = require("node-notifier");
const path = require("path");

const BASE_URL = "http://localhost";
const BOUNCER_KEY = process.env.BOUNCER_KEY;
const CLIENT_IP = process.env.CS_WP_HOST;
const WORDPRESS_VERSION = process.env.WORDPRESS_VERSION;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "my_very_very_secret_admin_password";
const LAPI_URL = process.env.LAPI_URL_FROM_CONTAINERS;
const NOTIFY = !!process.env.DEBUG;
const TIMEOUT = (!!process.env.DEBUG ? 5 * 60 : 8) * 1000;

const notify = (message) => {
	if (NOTIFY) {
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
const WP52 = WORDPRESS_VERSION === "5.2";
const WP51 = WORDPRESS_VERSION === "5.1";
const WP50 = WORDPRESS_VERSION === "5.0";
const WP49 = WORDPRESS_VERSION === "4.9";

const waitForNavigation = page.waitForNavigation();

const goToAdmin = async () => {
	await page.goto(`${BASE_URL}/wp-admin/`);
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

const publicHomepageShouldBeBanWall = async () => {
	await goToPublicPage();
	const title = await page.title();
	await expect(title).toContain("Oops..");
	await expect(page).toHaveText(
		".desc",
		"This page is protected against cyber attacks and your IP has been banned by our system."
	);
};

const publicHomepageShouldBeCaptchaWall = async () => {
	await goToPublicPage();

	const title = await page.title();
	await expect(title).toContain("Oops..");
	await expect(page).toHaveText(
		".desc",
		"Please complete the security check."
	);
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
	const title = await page.title();
	await expect(title).toContain("Just another WordPress site");
};

const banOwnIpForSeconds = async (seconds) => {
	await addDecision(CLIENT_IP, "ban", seconds + "s");
	await wait(1000);
};

const captchaOwnIpForSeconds = async (seconds) => {
	await addDecision(CLIENT_IP, "captcha", seconds + "s");
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

const publicHomepageShouldBecomeBanWallBeforeSeconds = async (seconds) => {};

const publicHomepageShouldBecomeCaptchaWallBeforeSeconds = async (
	seconds
) => {};

const publicHomepageShouldBecomeAvailableBeforeSeconds = async (seconds) => {};

const publicHomepageShouldStayBanWallForSeconds = async (seconds) => {};

const publicHomepageShouldStayCaptchaWallForSeconds = async (seconds) => {};

const publicHomepageShouldStayAccessibleForSeconds = async (seconds) => {};

describe(`Setup WordPress ${WORDPRESS_VERSION} and CrowdSec plugin`, () => {
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
		await page.goto(`${BASE_URL}/wp-admin/plugins.php'`);
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
});

describe(`Run in Live mode`, () => {
	it('Should reduce the cache durations"', async () => {
		notify("Run in Live mode");
		await onAdminGoToAdvancedPage();
		await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(1);
		await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(1);
		await onAdminSaveSettings();
	});

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
		page.selectOption('select[name=crowdsec_bouncing_level]', 'flex_boucing');
		await onAdminSaveSettings();

		// Should be a captcha wall
		await publicHomepageShouldBeCaptchaWall();
	});

	it('Should be accessible in Disabled mode"', async () => {
		
		// set Disabled mode
		await goToAdmin();
		await onAdminGoToSettingsPage();
		page.selectOption('select[name=crowdsec_bouncing_level]', 'bouncing_disabled');
		await onAdminSaveSettings();

		// Should be accessible
		await publicHomepageShouldBeAccessible();

		// Go back to normal mode
		await goToAdmin();
		await onAdminGoToSettingsPage();
		page.selectOption('select[name=crowdsec_bouncing_level]', 'normal_boucing');
		await onAdminSaveSettings();

		// Should be a ban wall
		await publicHomepageShouldBeBanWall();
	});

	it('Should display back the homepage with no remediation"', async () => {
		await removeAllDecisions();
		await publicHomepageShouldBeAccessible();
	});
});

describe(`Run in Stream mode`, () => {
	it('Should enable the stream mode"', async () => {
		notify("Run in Stream mode");

		await goToAdmin();
		await onAdminGoToAdvancedPage();

		await onAdvancedPageEnableStreamMode();

		await fillInput("crowdsec_stream_mode_refresh_frequency", 1);
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
});

/*
# Public website only

In live mode (1s + 1s), disable "Public website only"
Ban current IP during 5 sec
Try to access admin each 2 sec
The third time admin should be back
Re-enable "Public website only"

# Manually clear the cache

Set cache duration to 1min (clean + bad IP)
Remove all decisions + Ban current IP during 15min
The public page should be forbidden
Remove all decisions
The public page should still be forbidden
Click the "Clear now" button
The public page should be accessible

# Refresh cache button

(to write)

# Stream mode: Resync decisions each

Remove all decisions + Ban current IP during 15min
Set stream mode with 15 seconds resync
Refresh cache

(to finish, I'm gonna sleep)

# Loop avec Redis / Memcached (+ mauvais format DSN, DSN down)

(to write)

# Fallback

(to write)

# CDN IP

ban IP 1.2.3.4
In CDN list, add a large range including the current IP
Try to access homepage simulating 1.2.3.4 via the CDN : const page = await browser.newPage({ extraHTTPHeaders: 'X-Forwarded-For': '1.2.3.4' })
Should be banned


# Recheck clean IP

(to write)

# Recheck Bad IP

(to write)

# Prune FS Cache

(to write)

*/
