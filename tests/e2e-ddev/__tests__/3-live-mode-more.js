/* eslint-disable no-undef */
const { OTHER_IP } = require("../utils/constants");
const {
	notify,
	addDecision,
	wait,
	goToAdmin,
	onAdminGoToSettingsPage,
	onAdminGoToAdvancedPage,
	onAdvancedPageEnableStandAloneMode,
	onAdvancedPageDisableStandAloneMode,
	onAdminSaveSettings,
	publicHomepageShouldBeBanWall,
	publicHomepageShouldBeCaptchaWall,
	publicHomepageShouldBeAccessible,
	banIpForSeconds,
	removeAllDecisions,
	fillInput,
	loadCookies,
	enableAutoPrependFileInHtaccess,
	disableAutoPrependFileInHtaccess,
	onLoginPageLoginAsAdmin,
	setDefaultConfig,
	banOwnIpForSeconds,
} = require("../utils/helpers");

const { CURRENT_IP, PROXY_IP } = require("../utils/constants");

describe(`Run in Live mode`, () => {
	beforeAll(async () => {
		await removeAllDecisions();
		await goToAdmin();
		await onLoginPageLoginAsAdmin();
		await setDefaultConfig();
	});

	// it('Should use standalone mode"', async () => {
	// 	// Enable standalone mode (add a htaccess directive)
	// 	await goToAdmin();
	// 	await onAdminGoToAdvancedPage();
	// 	await onAdvancedPageEnableStandAloneMode();
	// 	await onAdminSaveSettings();

	// 	// Should still be a ban wall (but now using standalone mode)
	// 	await enableAutoPrependFileInHtaccess();
	// 	await wait(2000);
	// 	await publicHomepageShouldBeBanWall();

	// 	// Remove the standalone mode
	// 	await disableAutoPrependFileInHtaccess();
	// 	await wait(2000);
	// 	await goToAdmin();
	// 	await onAdminGoToAdvancedPage();
	// 	await onAdvancedPageDisableStandAloneMode();
	// 	await onAdminSaveSettings();

	// 	// Should be a captcha wall
	// 	await publicHomepageShouldBeBanWall();
	// });

	it('Should display a captcha wall instead of a ban wall in Flex mode"', async () => {
		// set Flex mode
		await goToAdmin();
		await onAdminGoToSettingsPage();
		await page.selectOption(
			"[name=crowdsec_bouncing_level]",
			"flex_boucing",
		);
		await onAdminSaveSettings();

		await banOwnIpForSeconds(15 * 60, CURRENT_IP);

		// Should be a captcha wall
		await publicHomepageShouldBeCaptchaWall();
	});

	it('Should be accessible in Disabled mode"', async () => {
		// set Disabled mode
		await goToAdmin();
		await onAdminGoToSettingsPage();
		await page.selectOption(
			"[name=crowdsec_bouncing_level]",
			"bouncing_disabled",
		);
		await onAdminSaveSettings();

		// Should be accessible
		await publicHomepageShouldBeAccessible();

		// Go back to normal mode
		await goToAdmin();
		await onAdminGoToSettingsPage();
		await page.selectOption(
			"[name=crowdsec_bouncing_level]",
			"normal_boucing",
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
		await addDecision(CURRENT_IP, "mfa", 15 * 60);
		await wait(1000);
		await publicHomepageShouldBeCaptchaWall();
		await goToAdmin();
		await onAdminGoToAdvancedPage();
		await page.selectOption(
			"[name=crowdsec_fallback_remediation]",
			"bypass",
		);
		await onAdminSaveSettings();
		await publicHomepageShouldBeAccessible();
	});

	it('Should handle X-Forwarded-For header for whitelisted IPs only"', async () => {
		await removeAllDecisions();
		await banIpForSeconds(CURRENT_IP, 15 * 60);

		// Remove PROXY IP from the CDN list (via a range)
		await goToAdmin();
		await onAdminGoToAdvancedPage();
		await fillInput("crowdsec_trust_ip_forward_list", "");
		await onAdminSaveSettings();

		// Should be accessible as PROXY IP is not trust by CDN
		// page.setExtraHTTPHeaders({ "X-Forwarded-For": OTHER_IP });
		await publicHomepageShouldBeAccessible();

		// Add the current IP to the CDN list (via a range)
		await goToAdmin();
		await onAdminGoToAdvancedPage();
		await fillInput("crowdsec_trust_ip_forward_list", `${PROXY_IP}/30`);
		await onAdminSaveSettings();

		// Should be banned
		await publicHomepageShouldBeBanWall();

		// Remove the XFF header for next requests
		// page.setExtraHTTPHeaders({});
	});
});
