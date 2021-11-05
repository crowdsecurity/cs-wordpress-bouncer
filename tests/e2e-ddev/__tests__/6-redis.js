/* eslint-disable no-undef */
const {
	notify,
	wait,
	goToAdmin,
	onAdminGoToAdvancedPage,
	onAdminSaveSettings,
	publicHomepageShouldBeBanWall,
	publicHomepageShouldBeAccessible,
	banOwnIpForSeconds,
	removeAllDecisions,
	forceCronRun,
	fillInput,
	loadCookies,
	onLoginPageLoginAsAdmin,
	setDefaultConfig,
	onAdvancedPageEnableStreamMode,
} = require("../utils/helpers");

const { CURRENT_IP } = require("../utils/constants");

describe(`Use Redis technology`, () => {
	beforeAll(async () => {
		await removeAllDecisions();
		await goToAdmin();
		await onLoginPageLoginAsAdmin();
		await setDefaultConfig();
	});

	it('Should be able to use Redis cache"', async () => {
		// TODO (+ bad DSN format, + DSN down)

		await goToAdmin();
		await onAdminGoToAdvancedPage();
		await onAdvancedPageEnableStreamMode();
		await page.selectOption("[name=crowdsec_cache_system]", "redis");
		await wait(200);
		await fillInput("crowdsec_redis_dsn", "redis://redis:6379"); // TODO test bad DSN format and test DSN down
		await onAdminSaveSettings();
		await expect(page).toHaveText(
			"#wpbody-content > div.wrap > div.notice.notice-success",
			"As the stream mode is enabled, the cache has just been warmed up, there is now 0 decision in cache.",
		);

		await publicHomepageShouldBeAccessible();
		await banOwnIpForSeconds(15 * 60, CURRENT_IP);
		await forceCronRun();
		await publicHomepageShouldBeBanWall();
		await removeAllDecisions();
		await forceCronRun();
		await publicHomepageShouldBeAccessible();
	});
});
