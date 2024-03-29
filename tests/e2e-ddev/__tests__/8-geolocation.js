/* eslint-disable no-undef */
const {
    removeAllDecisions,
    onLoginPageLoginAsAdmin,
    goToAdmin,
    setDefaultConfig,
    onAdminGoToSettingsPage,
    onAdminGoToAdvancedPage,
    fillInput,
    setToggle,
    onAdminSaveSettings,
    wait,
    publicHomepageShouldBeBanWall,
    publicHomepageShouldBeCaptchaWallWithMentions,
    publicHomepageShouldBeAccessible,
    selectByName,
} = require("../utils/helpers");

const { addDecision } = require("../utils/watcherClient");

const { FRANCE_IP, JAPAN_IP } = require("../utils/constants");

describe(`Geolocation and country scoped decision`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
    });

    it("Should retrieve good decisions with Country database", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await onAdminGoToAdvancedPage();
        // Prepare Geolocation test config
        await fillInput("crowdsec_forced_test_ip", FRANCE_IP);
        await setToggle("crowdsec_geolocation_enabled", true);
        await fillInput("crowdsec_geolocation_cache_duration", 0);
        await selectByName(
            "crowdsec_geolocation_maxmind_database_type",
            "country",
        );
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-Country.mmdb",
        );
        await onAdminSaveSettings();
        await addDecision("FR", "ban", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("FR", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        await addDecision(FRANCE_IP, "ban", 15 * 60, "Ip");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("JP", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeAccessible();
    });

    it("Should retrieve good decisions with City database", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await onAdminGoToAdvancedPage();

        // Prepare Geolocation test config
        await selectByName(
            "crowdsec_geolocation_maxmind_database_type",
            "city",
        );
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-City.mmdb",
        );
        await onAdminSaveSettings();
        await addDecision("FR", "ban", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("FR", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeCaptchaWallWithMentions();

        await addDecision(FRANCE_IP, "ban", 15 * 60, "Ip");
        await wait(1000);
        await publicHomepageShouldBeBanWall();

        await removeAllDecisions();
        await addDecision("JP", "captcha", 15 * 60, "Country");
        await wait(1000);
        await publicHomepageShouldBeAccessible();
    });

    it("Should call or not call the GeoIp database depending on cache duration config", async () => {
        await goToAdmin();
        await onAdminGoToSettingsPage();
        await onAdminGoToAdvancedPage();

        // Do not save result
        await fillInput("crowdsec_geolocation_cache_duration", 0);

        // Set a good path
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-City.mmdb",
        );
        await onAdminSaveSettings(true);
        await onAdminGoToSettingsPage();

        await fillInput("crowdsec_test_geolocation_ip", JAPAN_IP);

        await page.click("#crowdsec_action_test_geolocation #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Result is: JP/,
        );
        // Set a bad path to simulate bad database
        await onAdminGoToAdvancedPage();
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-FAKE.mmdb",
        );
        await onAdminSaveSettings(true);
        await onAdminGoToSettingsPage();

        await page.click("#crowdsec_action_test_geolocation #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /does not exist or is not readable./,
        );

        // Save result
        await onAdminGoToAdvancedPage();
        await fillInput("crowdsec_geolocation_cache_duration", 120);

        // Set a good path
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-City.mmdb",
        );
        await onAdminSaveSettings(true);
        await onAdminGoToSettingsPage();
        await fillInput("crowdsec_test_geolocation_ip", FRANCE_IP);

        await page.click("#crowdsec_action_test_geolocation #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Result is: FR/,
        );
        // Set a bad path to simulate bad database
        await onAdminGoToAdvancedPage();
        await fillInput(
            "crowdsec_geolocation_maxmind_database_path",
            "/var/www/html/crowdsec/geolocation/GeoLite2-FAKE.mmdb",
        );
        await onAdminSaveSettings(true);
        await onAdminGoToSettingsPage();
        await fillInput("crowdsec_test_geolocation_ip", FRANCE_IP);

        // Should not call the database
        await page.click("#crowdsec_action_test_geolocation #submit");
        await wait(2000);
        await expect(page).toMatchText(
            ".notice.notice-success",
            /Result is: FR/,
        );
    });
});
