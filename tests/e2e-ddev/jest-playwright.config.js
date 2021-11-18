const headless = process.env.HEADLESS;
const slowMo = parseFloat(process.env.SLOWMO);
module.exports = {
	launchOptions: {
		headless,
	},
	connectOptions: { slowMo },
	exitOnPageError: false,
	contextOptions: {
		ignoreHTTPSErrors: true,
		viewport: {
			width: 1920,
			height: 1080,
		},
	},
	browsers: ["chromium"],
	devices: ["Desktop Chrome"],
};
