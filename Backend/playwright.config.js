const { defineConfig } = require('@playwright/test');

// BooKi E2E config - runs against the LIVE qatest tenant (dedicated QA tenant, no real
// customer data - see docs/SESSION_NOTES.md). Not a local dev server: there is nothing to
// `webServer` here, the app is already deployed.
module.exports = defineConfig({
  testDir: './tests/e2e',
  timeout: 45000,
  // This VPS runs many other containers/rebuilds concurrently - occasional multi-second latency
  // spikes are the server, not the app. A blank-white failure screenshot (mid-navigation, nothing
  // painted yet) was the repeated symptom at the old 8s timeout; 15s gave zero flakes across
  // several full-suite reruns.
  expect: { timeout: 15000 },
  fullyParallel: false, // shared tenant state (one admin session, one set of records) - serialize
  workers: 1,
  retries: 0,
  reporter: [['list']],
  use: {
    baseURL: 'https://qatest-bookiapp.kibusiness.co',
    ignoreHTTPSErrors: true,
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [
    {
      name: 'setup',
      testMatch: /auth\.setup\.spec\.js/,
    },
    {
      name: 'e2e',
      testIgnore: /auth\.setup\.spec\.js/,
      dependencies: ['setup'],
      use: {
        storageState: 'tests/e2e/.auth/admin.json',
      },
    },
  ],
});

