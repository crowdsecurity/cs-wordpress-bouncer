const {
    BOUNCER_KEY,
	CLIENT_IP,
	ADMIN_LOGIN,
    ADMIN_PASSWORD,
    ADMIN_URL,
	LAPI_URL,
    OTHER_IP,
    WORDPRESS_VERSION,
	WP56,
	WP55,
	WP54,
    WP53
} = require("../utils/constants");

const {
    notify,
	addDecision,
	wait,
	waitForNavigation,
	goToAdmin,
	goToPublicPage,
	onAdminGoToSettingsPage,
	onAdminGoToAdvancedPage,
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
} = require("../utils/helpers");

describe(`Setup WordPress ${WORDPRESS_VERSION} and CrowdSec plugin`, () => {
	beforeEach(() => notify(expect.getState().currentTestName));

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
		await page.goto(`${ADMIN_URL}/plugins.php'`);
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
	beforeEach(() => notify(expect.getState().currentTestName));

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
	beforeEach(() => notify(expect.getState().currentTestName));

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
	beforeEach(() => notify(expect.getState().currentTestName));

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
	beforeEach(() => notify(expect.getState().currentTestName));

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
