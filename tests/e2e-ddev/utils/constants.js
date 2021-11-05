const BASE_URL = process.env.WORDPRESS_URL;
const ADMIN_URL = `${BASE_URL}/wp-admin/`;
const { BOUNCER_KEY } = process.env;

const { WORDPRESS_VERSION } = process.env;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "admin123";
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";
const LAPI_URL_FROM_WP = "http://crowdsec:8080";
const { LAPI_URL_FROM_PLAYWRIGHT } = process.env;
const { DEBUG } = process.env;
const { TIMEOUT } = process.env;
const { CURRENT_IP } = process.env;
const { PROXY_IP } = process.env;
const OTHER_IP = "1.2.3.4";
const WP58 = WORDPRESS_VERSION === "5.8";
const WP57 = WORDPRESS_VERSION === "5.7";
const WP56 = ["5.6", "565"].includes(WORDPRESS_VERSION);
const WP55 = WORDPRESS_VERSION === "5.5";
const WP54 = WORDPRESS_VERSION === "5.4";
const WP53 = WORDPRESS_VERSION === "5.3";

module.exports = {
	ADMIN_URL,
	BASE_URL,
	BOUNCER_KEY,
	CURRENT_IP,
	PROXY_IP,
	ADMIN_LOGIN,
	ADMIN_PASSWORD,
	LAPI_URL_FROM_WP,
	LAPI_URL_FROM_PLAYWRIGHT,
	OTHER_IP,
	WP58,
	WP57,
	WP56,
	WP55,
	WP54,
	WP53,
	DEBUG,
	TIMEOUT,
	WATCHER_LOGIN,
	WATCHER_PASSWORD,
	WORDPRESS_VERSION,
};
