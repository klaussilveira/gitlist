import { defineConfig } from 'cypress';

export default defineConfig({
  video: false,
  screenshotOnRunFailure: false,
  e2e: {
    baseUrl: 'http://0.0.0.0:8880',
    supportFile: false,
    specPattern: [
      'tests/cypress/integration/**/*.spec.{js,ts,jsx,tsx}',
      'tests/cypress/integration/**/*.cy.{js,ts,jsx,tsx}',
    ],
  },
});
