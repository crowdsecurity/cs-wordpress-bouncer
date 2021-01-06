const BASE_URL = "http://localhost";
const ADMIN_URL = `${BASE_URL}/wp-admin/`;
const BOUNCER_KEY = process.env.BOUNCER_KEY;
const CLIENT_IP = process.env.CS_WP_HOST;
const WORDPRESS_VERSION = process.env.WORDPRESS_VERSION;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "my_very_very_secret_admin_password";
const LAPI_URL = process.env.LAPI_URL_FROM_CONTAINERS;
const DEBUG = !!process.env.DEBUG;
const TIMEOUT = (!!process.env.DEBUG ? 5 * 60 : 8) * 1000;
const OTHER_IP = "1.2.3.4";
const WP56 = WORDPRESS_VERSION === "";
const WP55 = WORDPRESS_VERSION === "5.5";
const WP54 = WORDPRESS_VERSION === "5.4";
const WP53 = WORDPRESS_VERSION === "5.3";


module.exports = {
    ADMIN_URL,
    BASE_URL,
    BOUNCER_KEY,
	CLIENT_IP,
	ADMIN_LOGIN,
	ADMIN_PASSWORD,
	LAPI_URL,
	OTHER_IP,
	WP56,
	WP55,
	WP54,
    WP53,
    DEBUG,
    TIMEOUT,
    WORDPRESS_VERSION,
}