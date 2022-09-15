const BASE_URL = process.env.WORDPRESS_URL;
const ADMIN_URL = `${BASE_URL}/wp-admin/`;
const { BOUNCER_KEY } = process.env;

const { WORDPRESS_VERSION } = process.env;
const ADMIN_LOGIN = "admin";
const ADMIN_PASSWORD = "admin123";
const WATCHER_LOGIN = "watcherLogin";
const WATCHER_PASSWORD = "watcherPassword";
const LAPI_URL_FROM_WP = "https://crowdsec:8080";
const { LAPI_URL_FROM_PLAYWRIGHT } = process.env;
const { DEBUG } = process.env;
const { TIMEOUT } = process.env;
const { CURRENT_IP } = process.env;
const { PROXY_IP } = process.env;
const OTHER_IP = "1.2.3.4";
const WP59 = WORDPRESS_VERSION.startsWith("59");
const WP58 = WORDPRESS_VERSION.startsWith("58");
const WP57 = WORDPRESS_VERSION.startsWith("57");
const WP56 = WORDPRESS_VERSION.startsWith("56");
const WP55 = WORDPRESS_VERSION.startsWith("55");
const WP54 = WORDPRESS_VERSION.startsWith("54");
const WP53 = WORDPRESS_VERSION.startsWith("53");
const WP52 = WORDPRESS_VERSION.startsWith("52");
const WP51 = WORDPRESS_VERSION.startsWith("51");
const WP50 = WORDPRESS_VERSION.startsWith("50");
const WP49 = WORDPRESS_VERSION.startsWith("49");
const JAPAN_IP = "210.249.74.42";
const FRANCE_IP = "78.119.253.85";
const { PLUGIN_PATH, TLS_PATH } = process.env;
const AGENT_CERT_FILE = `agent.pem`;
const AGENT_KEY_FILE = `agent-key.pem`;
const CA_CERT_FILE = `ca-chain.pem`;
const BOUNCER_CERT_FILE = `bouncer.pem`;
const BOUNCER_KEY_FILE = `bouncer-key.pem`;

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
    WP59,
    WP58,
    WP57,
    WP56,
    WP55,
    WP54,
    WP53,
    WP52,
    WP51,
    WP50,
    WP49,
    DEBUG,
    TIMEOUT,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
    WORDPRESS_VERSION,
    JAPAN_IP,
    FRANCE_IP,
    AGENT_CERT_FILE,
    AGENT_KEY_FILE,
    CA_CERT_FILE,
    WATCHER_LOGIN,
    WATCHER_PASSWORD,
    BOUNCER_CERT_FILE,
    BOUNCER_KEY_FILE,
    PLUGIN_PATH,
    TLS_PATH,
};
