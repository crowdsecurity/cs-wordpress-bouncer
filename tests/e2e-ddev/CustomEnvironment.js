const PlaywrightEnvironment =
	require("jest-playwright-preset/lib/PlaywrightEnvironment").default;

class CustomEnvironment extends PlaywrightEnvironment {
	async handleTestEvent(event) {
		if (process.env.FAIL_FAST) {
			if (
				event.name === "hook_failure" ||
				event.name === "test_fn_failure"
			) {
				this.failedTest = true;
				const buffer = await this.global.page.screenshot({
					path: "screenshot.jpg",
					type: "jpeg",
					quality: 20,
				});
				console.debug("Screenshot:", buffer.toString("base64"));
			} else if (this.failedTest && event.name === "test_start") {
				// eslint-disable-next-line no-param-reassign
				event.test.mode = "skip";
			}
		}

		if (super.handleTestEvent) {
			await super.handleTestEvent(event);
		}
	}
}

module.exports = CustomEnvironment;
