/* eslint-disable no-undef */
const {
	wait,
	goToAdmin,
	onAdminGoToAdvancedPage,
	onAdminSaveSettings,
	onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo,
	onAdminAdvancedSettingsPageSetBadIpCacheDurationTo,
	publicHomepageShouldBeBanWall,
	publicHomepageShouldBeAccessible,
	banOwnIpForSeconds,
	removeAllDecisions,
	onLoginPageLoginAsAdmin,
	setDefaultConfig,
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Run in Live mode`, () => {
	beforeAll(async () => {
		await removeAllDecisions();
		await goToAdmin();
		await onLoginPageLoginAsAdmin();
		await setDefaultConfig();
	});

	it("Should prune the File system cache", async () => {
		await goToAdmin();
		await onAdminGoToAdvancedPage();
		await page.click("#crowdsec_prune_cache");

		await expect(page).toHaveText(
			"#wpbody-content > div.wrap > div.notice.notice-success",
			"CrowdSec cache has just been pruned.",
		);
	});

	it("Should clear the cache on demand", async () => {
		await onAdminGoToAdvancedPage();
		await onAdminAdvancedSettingsPageSetCleanIpCacheDurationTo(60);
		await onAdminAdvancedSettingsPageSetBadIpCacheDurationTo(60);
		await onAdminSaveSettings();
		await banOwnIpForSeconds(15 * 60, CURRENT_IP);
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

		await expect(page).toHaveText(
			"#wpbody-content > div.wrap > div.notice.notice-success",
			"CrowdSec cache has just been cleared.",
		);
		await publicHomepageShouldBeAccessible();
	});
});
