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
  collectCoverageFrom: [
    "src/**/*.{ts,tsx,js,jsx}",
    "!src/**/*.d.ts",
    "!src/**/*.stories.{ts,tsx}",
    "!src/**/index.{ts,tsx}",
    "!src/app/layout.tsx",
    "!src/app/page.tsx",
  ],
};

module.exports = createJestConfig(customJestConfig);
