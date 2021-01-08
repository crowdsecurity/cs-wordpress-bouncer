const { networkInterfaces } = require("os");

const BASE_URL = process.env.WORDPRESS_URL;
const ADMIN_URL = `${BASE_URL}/wp-admin/`;
const BOUNCER_KEY = process.env.BOUNCER_KEY;
let CLIENT_IP;
let CLIENT_IP_V6;
if (process.env.BROWSER_IP) {
    CLIENT_IP=process.env.BROWSER_IP;
} else {
    const networkIface = process.env.NETWORK_IFACE;
    const interfaces = networkInterfaces()[networkIface].filter(
        (i) => !i.internal
    );
    CLIENT_IP = interfaces.filter((i) => i.family === "IPv4")[0].address;
    const ipv6interfaces = interfaces.filter((i) => i.family === "IPv6");
    CLIENT_IP_V6 = ipv6interfaces.length ? ipv6interfaces[0].address : null;
}

const WORDPRESS_VERSION = process.env.WORDPRESS_VERSION;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "my_very_very_secret_admin_password";
const LAPI_URL = process.env.LAPI_URL_FROM_CONTAINERS;
const DEBUG = !!process.env.DEBUG;
const TIMEOUT = (!!process.env.DEBUG ? 5 * 60 : 8) * 1000;
const OTHER_IP = "1.2.3.4";
const WP56 = WORDPRESS_VERSION === "5.6";
const WP55 = WORDPRESS_VERSION === "5.5";
const WP54 = WORDPRESS_VERSION === "5.4";
const WP53 = WORDPRESS_VERSION === "5.3";

module.exports = {
    ADMIN_URL,
    BASE_URL,
    BOUNCER_KEY,
    CLIENT_IP,
    CLIENT_IP_V6,
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
};
