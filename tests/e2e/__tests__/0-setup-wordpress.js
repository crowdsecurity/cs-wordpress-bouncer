const {
    ADMIN_LOGIN,
    ADMIN_PASSWORD,
    WORDPRESS_VERSION,
    WP56,
    WP55,
    WP54,
    WP53,
} = require("../utils/constants");

const {
    notify,
    waitForNavigation,
    goToPublicPage,
} = require("../utils/helpers");

describe(`Setup WordPress ${WORDPRESS_VERSION}`, () => {
    beforeEach(() => notify(expect.getState().currentTestName));

    it('Should install wordpress"', async () => {
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
});
