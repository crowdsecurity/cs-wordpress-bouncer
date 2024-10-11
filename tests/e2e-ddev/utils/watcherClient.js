const axios = require("axios").default;
const https = require("https");
const fs = require("fs");

const {
    LAPI_URL_FROM_PLAYWRIGHT,
    AGENT_CERT_FILE,
    AGENT_KEY_FILE,
    CA_CERT_FILE,
    VARHTML_PATH,
} = require("./constants");

const httpsAgent = new https.Agent({
    rejectUnauthorized: true,
    cert: fs.readFileSync(`${VARHTML_PATH}${AGENT_CERT_FILE}`),
    key: fs.readFileSync(`${VARHTML_PATH}${AGENT_KEY_FILE}`),
    ca: fs.readFileSync(`${VARHTML_PATH}${CA_CERT_FILE}`),
});

const httpClient = axios.create({
    baseURL: LAPI_URL_FROM_PLAYWRIGHT,
    timeout: 5000,
    httpsAgent,
});

let authenticated = false;

const cidrToRange = (cidr) => {
    const range = [2];
    cidr = cidr.split("/");
    const cidr_1 = parseInt(cidr[1]);
    range[0] = long2ip(ip2long(cidr[0]) & (-1 << (32 - cidr_1)));
    start = ip2long(range[0]);
    range[1] = long2ip(start + Math.pow(2, 32 - cidr_1) - 1);
    return range;
};

const ip2long = (argIP) => {
    let i = 0;
    const pattern = new RegExp(
        [
            "^([1-9]\\d*|0[0-7]*|0x[\\da-f]+)",
            "(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?",
            "(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?",
            "(?:\\.([1-9]\\d*|0[0-7]*|0x[\\da-f]+))?$",
        ].join(""),
        "i",
    );
    argIP = argIP.match(pattern);
    if (!argIP) {
        return false;
    }
    argIP[0] = 0;
    for (i = 1; i < 5; i += 1) {
        argIP[0] += !!(argIP[i] || "").length;
        argIP[i] = parseInt(argIP[i]) || 0;
    }
    argIP.push(256, 256, 256, 256);
    argIP[4 + argIP[0]] *= Math.pow(256, 4 - argIP[0]);
    if (
        argIP[1] >= argIP[5] ||
        argIP[2] >= argIP[6] ||
        argIP[3] >= argIP[7] ||
        argIP[4] >= argIP[8]
    ) {
        return false;
    }
    return (
        argIP[1] * (argIP[0] === 1 || 16777216) +
        argIP[2] * (argIP[0] <= 2 || 65536) +
        argIP[3] * (argIP[0] <= 3 || 256) +
        argIP[4] * 1
    );
};

const auth = async () => {
    if (authenticated) {
        return;
    }
    try {
        const response = await httpClient.post("/v1/watchers/login", {});
        httpClient.defaults.headers.common.Authorization = `Bearer ${response.data.token}`;
        authenticated = true;
    } catch (error) {
        console.error(error);
    }
};

module.exports.addDecision = async (
    value,
    remediation,
    durationInSeconds,
    scope = "Ip",
) => {
    await auth();
    let finalScope = "Country";
    if (["Ip", "Range"].includes(scope)) {
        let startIp;
        let endIp;
        if (value.split("/").length === 2) {
            [startIp, endIp] = cidrToRange(value);
        } else {
            startIp = value;
            endIp = value;
        }
        const startLongIp = ip2long(startIp);
        const endLongIp = ip2long(endIp);
        const isRange = startLongIp !== endLongIp;
        finalScope = isRange ? "Range" : "Ip";
    }

    const scenario = `add ${remediation} with scope/value ${scope}/${value} for ${durationInSeconds} seconds for e2e tests`;

    const startAt = new Date();
    const stopAt = new Date();
    stopAt.setTime(stopAt.getTime() + durationInSeconds * 1000);
    const body = [
        {
            capacity: 0,
            decisions: [
                {
                    duration: `${durationInSeconds}s`,
                    origin: "cscli",
                    scenario,
                    scope: finalScope,
                    type: remediation,
                    value,
                },
            ],
            events: [],
            events_count: 1,
            labels: null,
            leakspeed: "0",
            message: scenario,
            scenario,
            scenario_hash: "",
            scenario_version: "",
            simulated: false,
            source: {
                scope: finalScope,
                value,
            },
            start_at: startAt.toISOString(),
            stop_at: stopAt.toISOString(),
        },
    ];
    try {
        await httpClient.post("/v1/alerts", body);
    } catch (error) {
        console.debug(error.response);
        throw new Error(error);
    }
};

module.exports.deleteAllDecisions = async () => {
    try {
        await auth();
        await httpClient.delete("/v1/decisions");
    } catch (error) {
        console.log(error.response || error);
        throw new Error(error);
    }
};
