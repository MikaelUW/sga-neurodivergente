// @ts-check
const { defineConfig, devices } = require('@playwright/test');

/**
 * E2E do SGA Neurodivergente.
 * Pré-requisito: a API deve estar no ar em http://localhost:8000
 * (docker compose up no backend). O servidor estático do frontend é
 * iniciado automaticamente pelo webServer abaixo.
 */
module.exports = defineConfig({
  testDir: './e2e',
  timeout: 30000,
  expect: { timeout: 10000 },
  fullyParallel: false,
  retries: 0,
  reporter: [['list']],
  use: {
    baseURL: 'http://localhost:5500',
    headless: true,
    screenshot: 'only-on-failure',
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
  webServer: {
    command: 'npm run serve',
    url: 'http://localhost:5500',
    reuseExistingServer: true,
    timeout: 20000,
  },
});
