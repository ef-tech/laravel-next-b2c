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
  },
  collectCoverageFrom: [
    "src/**/*.{ts,tsx,js,jsx}",
    "!src/**/*.d.ts",
    "!src/**/*.stories.{ts,tsx}",
    "!src/**/index.{ts,tsx}",
    "!src/app/layout.tsx",
    "!src/app/page.tsx",
  ],
  // Override JUnit reporter outputName for admin-app
  reporters: [
    "default",
    [
      "jest-junit",
      {
        outputDirectory: "<rootDir>/../../test-results/junit",
        outputName: "frontend-admin-results.xml",
        suiteName: "admin-app",
        classNameTemplate: "{classname}",
        titleTemplate: "{title}",
        ancestorSeparator: " › ",
        usePathForSuiteName: "true",
      },
    ],
  ],
};

module.exports = createJestConfig(customJestConfig);
