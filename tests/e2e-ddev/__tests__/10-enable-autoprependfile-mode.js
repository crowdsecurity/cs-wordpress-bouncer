const {
    goToAdmin,
    onLoginPageLoginAsAdmin,
    enableAutoPrependFileMode,
    wait,
    removeAllDecisions,
    setDefaultConfig,
    publicHomepageShouldBeAccessible,
    banOwnIpForSeconds,
    publicHomepageShouldBeBanWall,
    disableAutoPrependFileMode,
} = require("../utils/helpers");
const { CURRENT_IP } = require("../utils/constants");

// This test should be run with a server already configured with the auto_prepend_file mode
describe(`Auto Prepend File mode preparation`, () => {
    beforeAll(async () => {
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
        await removeAllDecisions();
        await setDefaultConfig();
        await disableAutoPrependFileMode();
    });

    it("Should not bounce before setting is enabled", async () => {
        await publicHomepageShouldBeAccessible();
        await banOwnIpForSeconds(15 * 60, CURRENT_IP);
        await publicHomepageShouldBeAccessible();
    });

    it("Should enable auto_prepend_file mode", async () => {
        await goToAdmin();
        await enableAutoPrependFileMode();
        await publicHomepageShouldBeBanWall();
    });
});
