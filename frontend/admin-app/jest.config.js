const nextJest = require("next/jest");
const base = require("../../jest.base");

const createJestConfig = nextJest({
  dir: __dirname,
});

const customJestConfig = {
  ...base,
  displayName: "admin-app",
  rootDir: __dirname,
  setupFilesAfterEnv: ["<rootDir>/../../jest.setup.ts"],
  moduleNameMapper: {
    "^@/(.*)$": "<rootDir>/src/$1",
    "^@shared/(.*)$": "<rootDir>/../lib/$1",
    // Security config module resolution for Jest
    "^\\.\\./security-config$": "<rootDir>/../security-config.ts",
  },
  collectCoverageFrom: [
    "src/**/*.{ts,tsx,js,jsx}",
    "!src/**/*.d.ts",
    "!src/**/*.stories.{ts,tsx}",
    "!src/**/index.{ts,tsx}",
    "!src/app/layout.tsx",
    "!src/app/page.tsx",
    "!src/app/global-error.tsx",
    // E2E test pages - excluded from coverage as they are test fixtures, not production code
    "!src/app/test-error/**",
    "!src/app/[locale]/test-error/**",
    // Development-only error test page - excluded from coverage as it's for manual testing
    "!src/app/simple-error-test/**",
    // Next.js App Router layout and pages - excluded from coverage as they are difficult to unit test
    // These are primarily tested through E2E tests
    "!src/app/[locale]/layout.tsx",
    "!src/app/[locale]/page.tsx",
    // next-intl configuration files - excluded from coverage as they are Server Component configs
    // These are validated through integration tests and E2E tests
    "!src/i18n.ts",
    "!src/middleware.ts",
    "!src/types/error-codes.ts",
  ],
};

module.exports = createJestConfig(customJestConfig);
