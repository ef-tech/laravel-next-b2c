const nextJest = require("next/jest");
const base = require("../../jest.base");

const createJestConfig = nextJest({
  dir: __dirname,
});

const customJestConfig = {
  ...base,
  displayName: "user-app",
  rootDir: __dirname,
  setupFilesAfterEnv: ["<rootDir>/../../jest.setup.ts"],
  moduleNameMapper: {
    "^@/(.*)$": "<rootDir>/src/$1",
    // Security config module resolution for Jest
    "^\\.\\./security-config$": "<rootDir>/../security-config.ts",
  },
  transformIgnorePatterns: [
    "node_modules/(?!(msw|@mswjs|until-async|next-intl|use-intl|.*\\.mjs$))",
  ],
  collectCoverageFrom: [
    "src/**/*.{ts,tsx,js,jsx}",
    "!src/**/*.d.ts",
    "!src/**/*.stories.{ts,tsx}",
    "!src/**/index.{ts,tsx}",
    "!src/app/layout.tsx",
    "!src/app/page.tsx",
    "!src/app/global-error.tsx",
    "!src/app/test-error/**",
    "!src/types/error-codes.ts",
    // Shared library files copied from monorepo (tested in monorepo root)
    "!src/lib/api-client.ts",
    "!src/lib/api-error.ts",
    "!src/lib/network-error.ts",
  ],
};

module.exports = createJestConfig(customJestConfig);
