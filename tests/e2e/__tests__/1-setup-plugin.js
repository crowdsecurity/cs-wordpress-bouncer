const {
	BOUNCER_KEY,
	ADMIN_URL,
	LAPI_URL_FROM_WP,
	WP56,
	WP55,
} = require("../utils/constants");

const {
	notify,
	waitForNavigation,
	goToAdmin,
	onAdminGoToSettingsPage,
	onAdminGoToAdvancedPage,
	onAdminSaveSettings,
	onLoginPageLoginAsAdmin,
	onAdvancedPageEnableDebugMode,
	onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo,
	onAdminAdvancedSettingsPageSetBadIpCacheDurationTo,
	fillInput,
	storeCookies,
	wait,
} = require("../utils/helpers");

describe(`Setup CrowdSec plugin`, () => {
	beforeEach(() => notify(expect.getState().currentTestName));

	it('Should login to wp-admin"', async () => {
		// "Login" page
		await goToAdmin();
		await onLoginPageLoginAsAdmin();
		await storeCookies();
	});

	it('Should install CrowdSec plugin"', async () => {
		// "Plugins" page
		await wait(2000);
		await page.goto(`${ADMIN_URL}/plugins.php`);
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
		await fillInput("crowdsec_api_url", LAPI_URL_FROM_WP);
		await fillInput("crowdsec_api_key", BOUNCER_KEY);
		await onAdminSaveSettings();
	});

	it('Should enable the debug mode"', async () => {
		await onAdminGoToAdvancedPage();
		await onAdvancedPageEnableDebugMode();
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
		await browser.close();
	});
});
