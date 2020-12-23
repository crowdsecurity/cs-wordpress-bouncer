const debug = process.env.DEBUG;
module.exports = {
	launchOptions: {
		headless: !debug,
		devtools: debug,
	},
	connectOptions: debug ? {slowMo: 150} : {},
	exitOnPageError: !debug,
	contextOptions: {
		ignoreHTTPSErrors: true,
		viewport: {
			width: 1920,
			height: 1080,
		},
	},
	browsers: ["chromium"],
	devices: [],
};
