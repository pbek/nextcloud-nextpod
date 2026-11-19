// @ts-check
const { defineConfig, devices } = require("@playwright/test");

module.exports = defineConfig({
  testDir: "./tests",
  timeout: 30000,
  forbidOnly: !!process.env.CI,
  workers: 1,
  reporter: "list",
  use: {
    baseURL: process.env.NEXTCLOUD_URL || "http://localhost:8081",
    colorScheme: "light",
    reducedMotion: "reduce",
    locale: "en-US",
    timezoneId: "UTC",
    trace: "retain-on-failure",
  },
  // Different browsers should not overwrite the same screenshots.
  projects: [
    {
      name: "chromium",
      use: {
        ...devices["Desktop Chrome"],
        viewport: { width: 1440, height: 900 },
        launchOptions: process.env.CHROMIUM_PATH
          ? { executablePath: process.env.CHROMIUM_PATH }
          : {},
      },
    },
  ],
});
