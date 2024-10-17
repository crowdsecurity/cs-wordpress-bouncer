const {
    goToAdmin,
    onLoginPageLoginAsAdmin,
    enableAutoPrependFileMode,
    wait,
} = require("../utils/helpers");

describe(`Setup CrowdSec plugin`, () => {
    it('Should login to wp-admin"', async () => {
        // "Login" page
        await goToAdmin();
        await onLoginPageLoginAsAdmin();
    });

    it('Should enable auto_prepend_file mode"', async () => {
        await wait(2000);
        await enableAutoPrependFileMode();
    });
});
