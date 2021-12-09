/* eslint-disable no-undef */
const { ADMIN_URL, WP58, WP57, WP56, WP55 } = require("../utils/constants");

const {
	waitForNavigation,
	goToAdmin,
	onLoginPageLoginAsAdmin,
	wait,
} = require("../utils/helpers");

describe(`Setup CrowdSec plugin`, () => {
	it('Should login to wp-admin"', async () => {
		// "Login" page
		await goToAdmin();
		await onLoginPageLoginAsAdmin();
	});

	it('Should install CrowdSec plugin"', async () => {
		// "Plugins" page
		await wait(2000);
		await page.goto(`${ADMIN_URL}/plugins.php`);
		if (WP55 || WP56 || WP57 || WP58) {
			await page.click("#activate-crowdsec");
		} else {
			await page.click('[aria-label="Activate CrowdSec"]');
		}

		await waitForNavigation;
		await expect(page).toHaveText("#message", "Plugin activated.");
	});
});
