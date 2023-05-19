/* eslint-disable no-undef */
const { WP59, WP58, WP57, WP56, WP55 } = require("../utils/constants");

const {
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
        await goToAdmin("/plugins.php");
        if (WP55 || WP56 || WP57 || WP58 || WP59) {
            await page.click("#activate-crowdsec");
        } else {
            await page.click('[aria-label="Activate CrowdSec"]');
        }

        await expect(page).toHaveText("#message", "Plugin activated.");
    });
});
