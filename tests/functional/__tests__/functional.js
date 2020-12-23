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

describe(`Run CrowdSec plugin on WordPress ${WORDPRESS_VERSION}`, () => {
	beforeAll(async () => {});

	afterAll(async () => {
		return await browser.close();
	});

	const waitForNavigation = page.waitForNavigation();

	it('Should install wordpress"', async () => {
		notify("Install wordpress");

		// Go to home
		await page.goto(`${BASE_URL}`);

		if (WP54 || WP55 || WP56) {
			// "Language selection" page
			await page.click('option[lang="en"]');
			await page.click("#language-continue");
			await waitForNavigation;
		}

		// "Account creation" page
		await page.fill("#weblog_title", "My website");
		await page.fill("#user_login", ADMIN_LOGIN);
		if (WP53 ||WP54 ||WP55 ||WP56) {
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
		notify("Login to wp-admin");
		// "Login" page
		await page.fill("#user_login", ADMIN_LOGIN);
		await page.fill("#user_pass", ADMIN_PASSWORD);
		await page.waitForSelector("#wp-submit");
		await page.click("#wp-submit");
		await waitForNavigation;
	});

	it('Should install CrowdSec plugin"', async () => {
		notify("Install CrowdSec plugin");
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
		notify("Configure the connection details");
		// CrowdSec Menu
		await page.click(
			"#adminmenuwrap > #adminmenu > #toplevel_page_crowdsec_plugin > .wp-has-submenu > .wp-menu-name"
		);
		await waitForNavigation;

		// CrowdSec Settings page: set connection details

		await page.fill("[name=crowdsec_api_url]", LAPI_URL);
		await page.fill("[name=crowdsec_api_key]", BOUNCER_KEY);
		await page.click("[type=submit]");
		await waitForNavigation;

		await expect(page).toHaveText(
			"#setting-error-settings_updated",
			"Settings saved."
		);
	});

	it('Should reduce the cache durations"', async () => {
		notify("Reduce the cache durations");
		// CrowdSec Menu
		await page.click(
			"#toplevel_page_crowdsec_plugin > ul > li:nth-child(3) > a"
		);
		await waitForNavigation;

		// CrowdSec Settings page: set connection details

		await page.fill("[name=crowdsec_clean_ip_cache_duration]", "1");
		await page.fill("[name=crowdsec_bad_ip_cache_duration]", "1");
		await page.click("[type=submit]");
		await waitForNavigation;

		await expect(page).toHaveText(
			"#setting-error-settings_updated",
			"Settings saved."
		);
	});

	it('Should display the homepage with no remediation"', async () => {
		notify("Display the homepage with no remediation");

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Just another WordPress site");
	});

	it('Should display a captcha wall"', async () => {
		notify("Display a captcha wall");

		// add a captcha remediation
		await addDecision(CLIENT_IP, "captcha", "15m");
		await wait(1000);

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Oops..");
		await expect(page).toHaveText(
			".desc",
			"Please complete the security check."
		);

		// Refresh the captcha 2 times
		await page.click("#refresh_link");
		await waitForNavigation;

		await page.click("#refresh_link");
		await waitForNavigation;
	});

	it('Should display a ban wall"', async () => {
		notify("Display a ban wall");

		// add a ban remediation
		await addDecision(CLIENT_IP, "ban", "15m");
		await wait(1000);

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Oops..");
		await expect(page).toHaveText(
			".desc",
			"This page is protected against cyber attacks and your IP has been banned by our system."
		);
	});

	it('Should display back the homepage with no remediation"', async () => {
		notify("Display back the homepage with no remediation");

		// delete a ban remediation
		await deleteAllDecisions();
		await wait(1000);

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Just another WordPress site");
	});

	it('Should enable the stream mode"', async () => {
		notify("Enable the stream mode");

		await page.goto(
			`${BASE_URL}/wp-admin/admin.php?page=crowdsec_advanced_settings`
		);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Advanced");

		await page.click("[for=crowdsec_stream_mode]");

		await page.fill("[name=crowdsec_stream_mode_refresh_frequency]", "1");
		await page.click("[type=submit]");
		await waitForNavigation;

		await expect(page).toHaveText(
			"#setting-error-settings_updated",
			"Settings saved."
		);
	});

	it('Should display a ban wall via stream mode"', async () => {
		notify("Display a ban wall via stream mode");

		// add a ban remediation
		await addDecision(CLIENT_IP, "ban", "15m");
		await wait(2000);
		// force WP Cron to run cache update as bouncing is done before cache updating
		// This could be fixed by running homemade call to cache update
		// if it's the time to update cache
		await page.goto(`${BASE_URL}/wp-cron.php`);

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Oops..");
		await expect(page).toHaveText(
			".desc",
			"This page is protected against cyber attacks and your IP has been banned by our system."
		);
	});

	it('Should display back the homepage with no remediation via stream mode"', async () => {
		notify("Display back the homepage with no remediation via stream mode");

		// delete a ban remediation
		await deleteAllDecisions();
		await wait(2000);
		await page.goto(`${BASE_URL}/wp-cron.php`);

		await page.goto(`${BASE_URL}`);
		await waitForNavigation;

		const title = await page.title();
		await expect(title).toContain("Just another WordPress site");
	});
});
